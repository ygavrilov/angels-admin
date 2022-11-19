<?php 

namespace src\controllers;

class peppers {

    private $peppers = [];

    private $peppers_file_name = '../storage/peppers.json';
    private $images_foler_prefix = '../webroot/';
    private $images_folder = 'images';
    
    private $model_alias = 'peppers';

    private $model = [
        'name'  => FILTER_SANITIZE_STRING,
        'alias' => FILTER_SANITIZE_STRING,
        'desc'  => FILTER_SANITIZE_STRING
    ];

    public function __construct()
    {
        //  conect data source and load all peppers 
        header('Content-Type:application/json');
        $this->peppers = json_decode(file_get_contents($this->peppers_file_name), true);
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
        echo json_encode([
            'code'      => 200,
            'peppers'   => $this->peppers
        ]);
        return;
    }

    public function get_by_id(int $id = null)
    {
        if ($id == null) 
        {
            echo json_encode([
                'code'      => 400,
                'message'   => 'id is not provided'
            ]);
            return;
        }

        foreach ($this->peppers as $pepper)
        {
            if ($pepper['id'] == $id) {
                break;
            }
        }

        if ($pepper['id'] != $id)
        {
            echo json_encode([
                'code'      => 400,
                'message'   => 'pepper not found'
            ]);
            return;    
        }

        echo json_encode([
            'code'          => 200,
            'pepper'        => $pepper
        ]);
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
        foreach ($this->peppers as $pepper) 
        {
            if ($pepper['id'] > $max_id) 
            {
                $max_id = $pepper['id'];
            }
        }
        $new_record['id'] = $max_id + 1;
        

        if (!empty($_FILES) && $_FILES['file']['error'] == 0)
        {
            $new_record['img'][] = $this->upload_image($_FILES['file'], $new_record['id']);
        }

        $this->peppers[$new_record['id']] = $new_record;
        file_put_contents($this->peppers_file_name, json_encode($this->peppers));

        echo json_encode(
            [
                'code'          => 201,
                'message'       => 'new record created',
                'new_record'    => $new_record
            ]
        );
        return;
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') 
        {
            echo json_encode(
                [
                    'code'      => 400,
                    'message'   => 'Only POST request is accepted',
                    'method'    => $_SERVER['REQUEST_METHOD']
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

        $pepper_to_edit = null;
        foreach ($this->peppers as $pepper_index => $pepper) 
        {
            if ($pepper['id'] == $_POST['id'])
            {
                $pepper_to_edit = $pepper;
                break;
            }
        }

        if (empty($pepper_to_edit))
        {
            echo json_encode(
                [
                    'code'      => 400,
                    'message'   => 'pepper not found'
                ]
            );
            return;
        }

        $pepper_to_edit = (array) $pepper_to_edit;
        foreach ($this->model as $field_name => $field_filter)
        {
            if (array_key_exists($field_name, $_POST) === false) {
                continue;
            }
            $pepper_to_edit[$field_name] = filter_var($_POST[$field_name], $field_filter);
        }
        
        if (!empty($_FILES) && $_FILES['file']['error'] == 0)
        {
            $pepper_to_edit['img'][] = $this->upload_image($_FILES['file'], $pepper_to_edit['id']);
        }

        $this->peppers[$pepper_index] = $pepper_to_edit;
        file_put_contents($this->peppers_file_name, json_encode($this->peppers));

        echo json_encode(
            [
                'code'      => 200,
                'message'   => 'Record successfully updated'
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

        $item_index = null;
        foreach ($this->peppers as $index => $item)
        {
            if (array_key_exists('img', $item) === false) {
                continue;
            }
            if (in_array($image_file_name, $item['img'])) {
                $item_index = $index;
            }
        }

        if (empty($item_index))
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

        unset($this->peppers[$item_index]['img'][array_search($image_file_name, $this->peppers[$item_index]['img'])]);
        file_put_contents($this->peppers_file_name, json_encode($this->peppers));

        echo json_encode(
            [
                'code'          => 201,
                'message'       => 'image deleted'
            ]
        );
        return;
    }
}