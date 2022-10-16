<?php 

namespace src\controllers;

class items {

    private $items = [];

    public function __construct()
    {
        //  initialize items
        header('Content-Type:application/json');
        $this->items = json_decode(file_get_contents('../storage/items.json'));
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
    }

}