<?php

class Combination_Item
{
    // Values
    public $combination_id;
    public $category;
    public $collection;
    public $model;
    public $variant;
    public $finish;
    public $color;

    // WordPress Codes taxonomy_id:term_id
    public $category_term_taxonomy_id;
    public $category_term_id;

    public $collection_term_taxonomy_id;
    public $collection_term_id;

    public $model_term_taxonomy_id;
    public $model_term_id;

    public $variant_term_taxonomy_id;
    public $variant_term_id;

    public $finish_term_taxonomy_id;
    public $finish_term_id;

    public $color_term_taxonomy_id;
    public $color_term_id;

    // Image URLs
    public $image_png;
    public $image_jpg;

    public function __construct($data = [])
    {
        if (count($data) > 0) {
            $this->hydrate($data);
        }
    }

    public function hydrate($data)
    {

        // Order is hard-coded
        $this->combination_id = $data[0];
        $this->category = $data[1];
        $this->collection = $data[2];
        $this->model = $data[3];
        $this->variant = $data[4];
        $this->variant = trim($this->variant) ? $this->variant : 'No Variant';
        $this->finish = $data[5];
        $this->color = $data[6];
        $this->image_png = $data[7];
        $this->image_jpg = $data[8];

        // Fetch the custom taxonomies/terms combos
        $category_term = term_exists($this->category, 'combination_category');
        $this->category_term_taxonomy_id = $category_term ? intval($category_term['term_taxonomy_id']) : false;
        $this->category_term_id = $category_term ? intval($category_term['term_id']) : false;

        $collection_term = term_exists($this->collection, 'combination_category', $this->category_term_id);
        $this->collection_term_taxonomy_id = $collection_term ? intval($collection_term['term_taxonomy_id']) : false;
        $this->collection_term_id = $collection_term ? intval($collection_term['term_id']) : false;

        $model_term = term_exists($this->model, 'combination_category', $this->collection_term_id);
        $this->model_term_taxonomy_id = $model_term ? intval($model_term['term_taxonomy_id']) : false;
        $this->model_term_id = $model_term ? intval($model_term['term_id']) : false;

        $variant_term = term_exists($this->variant, 'combination_category', $this->model_term_id);
        $this->variant_term_taxonomy_id = $variant_term ? intval($variant_term['term_taxonomy_id']) : false;
        $this->variant_term_id = $variant_term ? intval($variant_term['term_id']) : false;

        $finish_term = term_exists($this->finish, 'combination_category', $this->variant_term_id);
        $this->finish_term_taxonomy_id = $finish_term ? intval($finish_term['term_taxonomy_id']) : false;
        $this->finish_term_id = $finish_term ? intval($finish_term['term_id']) : false;

        $color_term = term_exists($this->color, 'combination_category', $this->finish_term_id);
        $this->color_term_taxonomy_id = $color_term ? intval($color_term['term_taxonomy_id']) : false;
        $this->color_term_id = $color_term ? intval($color_term['term_id']) : false;
    }
}