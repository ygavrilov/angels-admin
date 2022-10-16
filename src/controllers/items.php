<?php 

namespace src\controllers;

class items {

    private $items = [];

    private $items_file_name = '../storage/items.json';

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
        $this->items = (array)json_decode(file_get_contents($this->items_file_name));
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
            "code" => 200,
            "items" => $this->items
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
            if ($item->id == $id) {
                break;
            }
        }

        if ($item->id != $id)
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
        if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') 
        {
            echo json_encode(
                [
                    'code'      => 400,
                    'message'   => 'Only PATCH request is accepted'
                ]
            );
            return;
        }
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
            if ($item->id > $max_id) 
            {
                $max_id = $item->id;
            }
        }
        $new_record['id'] = $max_id + 1;
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

}