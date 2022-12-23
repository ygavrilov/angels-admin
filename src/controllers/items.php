<?php 

namespace src\controllers;

class items {

    private $items = [];

    private $items_file_name = '../storage/items.json';
    private $images_foler_prefix = '../webroot/';
    private $images_folder = 'images';

    private $model_alias = 'items';

    private $model = [
        'name' => FILTER_SANITIZE_STRING,
        'comp' => FILTER_SANITIZE_STRING,
        'desc' => FILTER_SANITIZE_STRING,
        'price' => FILTER_SANITIZE_NUMBER_INT
    ];

    public function __construct()
    {
        //  initialize items
        header('Content-Type:application/json');
        $this->items = json_decode(file_get_contents($this->items_file_name), true);
    }

    public function list()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') 
        {
            echo json_encode(
                [
                    'code'      => 400,
                    'message'   => 'Only GET request is accepted'
                ]
            );
            return;
        }
        $items_with_ids_as_array_keys = [];
        foreach ($this->items as $item)
        {
            $items_with_ids_as_array_keys[$item['id']] = $item;
        }
        echo json_encode([
            "code" => 200,
            "items" => $items_with_ids_as_array_keys
        ]);
        return;
    }

    public function get_by_id(int $id = null)
    {
        if ($id == null) 
        {
            echo json_encode([
                "code"      => 400,
                "message"   => 'id is not provided'
            ]);
            return;
        }

        foreach ($this->items as $item)
        {
            if ($item['id'] == $id) {
                break;
            }
        }

        if ($item['id'] != $id)
        {
            echo json_encode([
                "code"      => 400,
                "message"   => "item not found"
            ]);
            return;    
        }

        echo json_encode([
            "code"      => 200,
            "item"      => $item
        ]);
        return;
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') 
        {
            echo json_encode(
                [
                    'code'      => 400,
                    'message'   => 'Only POST request is accepted'
                ]
            );
            return;
        }

        //  id must be present 
        //  data must be cleaned
        //  files need to be uploaded

        if (array_key_exists('id', $_POST) === false)
        {
            echo json_encode(
                [
                    'code'      => 400,
                    'message'   => 'id must be present'
                ]
            );
            return;
        }

        $item_to_edit = null;
        foreach ($this->items as $item_index => $item) 
        {
            if ($item['id'] == $_POST['id'])
            {
                $item_to_edit = $item;
                break;
            }
        }

        if (empty($item_to_edit))
        {
            echo json_encode(
                [
                    'code'      => 400,
                    'message'   => 'item not found'
                ]
            );
            return;
        }

        $item_to_edit = (array) $item_to_edit;
        foreach ($this->model as $field_name => $field_filter)
        {
            if (array_key_exists($field_name, $_POST) === false) {
                continue;
            }
            $item_to_edit[$field_name] = filter_var($_POST[$field_name], $field_filter);
        }
        
        if (!empty($_FILES) && $_FILES['file']['error'] == 0)
        {
            $item_to_edit['img'][] = $this->upload_image($_FILES['file'], $item_to_edit['id']);
        }

        $this->items[$item_index] = $item_to_edit;
        file_put_contents($this->items_file_name, json_encode($this->items));

        echo json_encode(
            [
                'code'      => 200,
                'message'   => 'Record successfully updated'
            ]
        );
        return;
    }

    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') 
        {
            echo json_encode(
                [
                    'code'      => 400,
                    'message'   => 'Only POST request is accepted'
                ]
            );
            return;
        }

        //  sanitize data
        //  validate data
        //  create a new record
        //  return new record
        
        $new_record = [];
        foreach ($this->model as $field_name => $field_filter)
        {
            if (array_key_exists($field_name, $_POST) === false) {
                continue;
            }
            $new_record[$field_name] = filter_var($_POST[$field_name], $field_filter);
        }

        $max_id = 1;
        foreach ($this->items as $item) 
        {
            if ($item['id'] > $max_id) 
            {
                $max_id = $item['id'];
            }
        }
        $new_record['id'] = $max_id + 1;
        

        if (!empty($_FILES) && $_FILES['file']['error'] == 0)
        {
            $new_record['img'][] = $this->upload_image($_FILES['file'], $new_record['id']);
        }

        $this->items[$new_record['id']] = $new_record;
        file_put_contents($this->items_file_name, json_encode($this->items));

        echo json_encode(
            [
                'code'          => 201,
                'message'       => 'new record created',
                'new_record'    => $new_record
            ]
        );
        return;
    }

    public function upload_image($file, $id)
    {
        //  upload image, put it in img array
        $image_name = 
            $this->images_folder .
            '/' .
            "{$this->model_alias}-$id-" . 
            hash_hmac('sha256', time(), $this->model_alias) . 
            '.' .
            substr($file['name'], strrpos($file['name'], '.') + 1)
        ;
        move_uploaded_file($file['tmp_name'], $this->images_foler_prefix . $image_name);
        return $image_name;
    }

    public function remove_image($image_file_name)
    {
        //  loop through items to find the image 
        //  delete 
        //  update 

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') 
        {
            echo json_encode(
                [
                    'code'      => 400,
                    'message'   => 'Only POST request is accepted'
                ]
            );
            return;
        }

        $item_index = false;
        foreach ($this->items as $index => $item)
        {
            if (array_key_exists('img', $item) === false) {
                continue;
            }
            if (in_array($image_file_name, $item['img'])) {
                $item_index = $index;
            }
        }

        if ($item_index === false)
        {
            echo json_encode(
                [
                    'code'          => 400,
                    'message'       => 'image not found',
                    'file'          => $image_file_name,
                    'item'          => $item
                ]
            );
            return;
        }

        try {
            unlink($image_file_name);
        } catch (Exception $e) {
            echo json_encode(
                [
                    'code'          => 500,
                    'message'       => 'something went wrong',
                    'new_record'    => $e
                ]
            );
            return;
        }

        unset($this->items[$item_index]['img'][array_search($image_file_name, $this->items[$item_index]['img'])]);
        file_put_contents($this->items_file_name, json_encode($this->items));

        echo json_encode(
            [
                'code'          => 201,
                'message'       => 'image deleted'
            ]
        );
        return;
    }

    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') 
        {
            echo json_encode([
                'code'      => 400,
                'message'   => 'Only POST request is accepted'
            ]);
            return;
        }

        if (array_key_exists('id', $_POST) === false)
        {
            echo json_encode([
                'code'      => 400,
                'message'   => 'id must be present'
            ]);
            return;
        }

        $item_to_delete = false;
        $this->items = (array) $this->items;
        foreach ($this->items as $item_index => $item) 
        {
            if ($item['id'] == $_POST['id'])
            {
                $item_to_delete = $item;
                $item_index_in_array = $item_index;
                break;
            }
        }

        if ($item_to_delete === false)
        {
            echo json_encode([
                'code'      => 400,
                'message'   => 'item not found'
            ]);
            return;
        }

        $all_images_deleted = true;
        foreach ($item_to_delete['img'] as $image_file_name)
        {
            try {
                unlink($image_file_name);
            } catch (Exception $e) {
                $all_images_deleted = false;
            }
        }

        if ($all_images_deleted)
        {
            unset($this->items[$item_index_in_array]);
            file_put_contents($this->items_file_name, json_encode($this->items));
            echo json_encode([
                'code'      => 200,
                'message'   => 'item deleted'
            ]);
            return;
        }

        echo json_encode([
            'code'      => 400,
            'message'   => 'item not deleted as there are errors deleting some images'
        ]);
        return;

    }

    public function move_up()
    {
        /**
         * must be post
         * must have ID 
         * ID must exist
         * 
         */

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') 
        {
            echo json_encode([
                'code'      => 400,
                'message'   => 'Only POST request is accepted'
            ]);
            return;
        }

        if (array_key_exists('id', $_POST) === false)
        {
            echo json_encode([
                'code'      => 400,
                'message'   => 'id must be present'
            ]);
            return;
        }

        $item_to_move_up = false;
        $this->items = (array) $this->items;
        foreach ($this->items as $item_index => $item) 
        {
            if ($item['id'] == $_POST['id'])
            {
                $item_to_move_up = $item;
                $item_index_in_array = $item_index;
                break;
            }
        }

        if ($item_to_move_up === false)
        {
            echo json_encode([
                'code'      => 400,
                'message'   => 'item not found'
            ]);
            return;
        }

        if ($item_index === 0)
        {
            echo json_encode([
                'code'      => 400,
                'message'   => 'item already on top'
            ]);
            return;
        }

        $item_to_move_down = $this->items[$item_index_in_array - 1];
        $this->items[$item_index_in_array - 1] = $item_to_move_up;
        $this->items[$item_index_in_array] = $item_to_move_down;

        file_put_contents($this->items_file_name, json_encode($this->items));
        echo json_encode([
            'code'      => 200,
            'message'   => 'item moved up'
        ]);
        return;
    }

}