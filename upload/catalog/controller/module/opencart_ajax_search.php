<?php
class ControllerModuleOpencartAjaxSearch extends Controller {
	public function index() {
		$json = array();

		if (isset($this->request->post['search'])) {
			$search = $this->request->post['search'];
		} else {
			$search = '';
		}

		if ( version_compare(VERSION, '2.0.0.0', '>=') && version_compare(VERSION, '2.2.0.0', '<') ) {
			$currency_code = null;
		} elseif( version_compare(VERSION, '2.2.0.0', '>=') && version_compare(VERSION, '2.4.0.0', '<') ){
			$currency_code = $this->session->data['currency'];
		} else {
            $currency_code = null;
            $json['error'] = 'Version Error: ' . VERSION;
		}

		if ( empty($json['error']) && $search ) {

            $this->load->language('module/opencart_ajax_search');

            $text_view_all_results = $this->config->get('opencart_ajax_search_view_all_results');

            $content = array();

            $products_search = $this->searchProducts($search, $currency_code);

            if (!empty($products_search)) {
                $content[] = array(
                    'items' =>  $products_search,
                    'title' =>  $this->language->get('title_products'),
                    'type'  =>  'products'
                );
            }

            $information_search = $this->searchInformation($search);

            if (!empty($information_search)) {
                $content[] = array(
                    'items' =>  $information_search,
                    'title' =>  $this->language->get('title_information'),
                    'type'  =>  'information'
                );
            }

            $news_search = $this->searchNews($search);

            if (!empty($news_search)) {
                $content[] = array(
                    'items' =>  $news_search,
                    'title' =>  $this->language->get('title_news'),
                    'type'  =>  'news'
                );
            }

            $categories_search = $this->searchCategories($search);

            if (!empty($categories_search)) {
                $content[] = array(
                    'items' =>  $categories_search,
                    'title' =>  $this->language->get('title_categories'),
                    'type'  =>  'categories'
                );
            }

            $json = array(
                'opencart_ajax_search_href' => $this->url->link('product/search', 'search=' . $search),
                'text_view_all_results'     => htmlspecialchars($text_view_all_results[$this->config->get('config_language_id')]['name']),
                'content'                   => $content
            );
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

    private function searchProducts($search, $currency_code) {
        $output = array();

        $this->load->language('product/search');

        $this->load->model('catalog/product');
        $this->load->model('tool/image');

        $filter_data = array(
            'filter_name'         => $search,
            'filter_tag'          => '',
            'filter_description'  => '',
            'filter_category_id'  => '',
            'filter_sub_category' => '',
            'sort'                => 'filter_name',
            'order'               => 'ASC',
            'start'               => 0,
            'limit'               => $this->config->get('opencart_ajax_search_products_limit')
        );

        $results = $this->model_catalog_product->getProducts($filter_data);

        if (empty($results)) return false;


        $image_width = $this->config->get('opencart_ajax_search_image_width');
        $image_height = $this->config->get('opencart_ajax_search_image_height');
        $title_length = $this->config->get('opencart_ajax_search_title_length');
        $description_length = $this->config->get('opencart_ajax_search_description_length');

        foreach ($results as $result) {
            if ($this->config->get('opencart_ajax_search_show_image') && $result['image']) {
                $image = $this->model_tool_image->resize($result['image'], $image_width, $image_height);
            } else {
                $image = false;
            }

            if ($this->config->get('opencart_ajax_search_show_price')) {
                if ((($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) && $result['price'] > 0) {
                    $price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $currency_code);
                } else {
                    $price = false;
                }

                if ((float)$result['special']) {
                    $special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $currency_code);
                } else {
                    $special = false;
                }
            } else {
                $price = false;
                $special = false;
            }

            $output[] = array(
                'id'            => $result['product_id'],
                'image'         => $image,
                'name'          => $this->getShortText($result['name'], $title_length),
                'extra_info'    => $this->config->get('opencart_ajax_search_show_description') ? $this->getShortText($result['description'], $description_length) : false,
                'price'         => $price,
                'special'       => $special,
                'url'           => $this->url->link('product/product', 'product_id=' . $result['product_id'])
            );
        }

        return $output;
    }

    private function searchCategories($search) {
        $output = array();

        $this->load->model('catalog/category');
        $this->load->model('tool/image');

        $filter_data = array(
            'filter_name'         => $search,
            'filter_description'  => false,
            'sort'                => 'filter_name',
            'order'               => 'ASC',
            'start'               => 0,
            'limit'               => $this->config->get('opencart_ajax_search_categories_limit')
        );

        $results = $this->model_catalog_category->searchCategories($filter_data);

        if (empty($results)) return false;

        $title_length = $this->config->get('opencart_ajax_search_title_length');

        foreach ($results as $result) {
            $filter_data = array(
                'filter_name'         => $search,
                'filter_description'  => false,
                'filter_category_id'  => $result['category_id'],
                'filter_sub_category' => true,
            );

            $product_total = $this->model_catalog_product->getTotalProducts($filter_data);

            if ( $product_total ) {
                $url = $this->url->link('product/search', 'search=' . $search . '&category_id=' . $result['category_id'] . '&sub_category=true');
            } else {
                $url = $this->url->link('product/category', 'path=' . $result['category_id']);

            }


            $output[] = array(
                'id'            => $result['category_id'],
                'name'          => sprintf($this->language->get('text_category_search'), $search, $this->getShortText($result['name'], $title_length)),
                'url'           => $url
            );
        }


        return $output;
    }

    private function searchInformation($search) {
        $output = array();

        $this->load->model('catalog/information');

        $filter_data = array(
            'filter_name'         => $search,
            'filter_description'  => true,
            'sort'                => 'filter_name',
            'order'               => 'ASC',
            'start'               => 0,
            'limit'               => $this->config->get('opencart_ajax_search_pages_limit')
        );

        $results = $this->model_catalog_information->searchInformation($filter_data);

        if (empty($results)) return false;

        $title_length = $this->config->get('opencart_ajax_search_title_length');

        foreach ($results as $result) {
            $output[] = array(
                'id'            => $result['information_id'],
                'name'          => $this->getShortText($result['title'], $title_length),
                'url'           => $this->url->link('information/information', 'information_id=' .  $result['information_id'])
            );
        }


        return $output;
    }

    private function searchNews($search) {
        if ( !$this->modelExists('catalog/news') ) return false;

        $output = array();

        $this->load->model('catalog/news');

        $filter_data = array(
            'filter_name'         => $search,
            'filter_description'  => false,
            'sort'                => 'filter_name',
            'order'               => 'ASC',
            'start'               => 0,
            'limit'               => $this->config->get('opencart_ajax_search_news_limit')
        );

        $results = $this->model_catalog_news->searchNews($filter_data);

        if (empty($results)) return false;

        $title_length = $this->config->get('opencart_ajax_search_title_length');

        foreach ($results as $result) {
            $output[] = array(
                'id'            => $result['news_id'],
                'name'          => $this->getShortText($result['title'], $title_length),
                'url'           => $this->url->link('information/news', 'news_id=' .  $result['news_id'])
            );
        }


        return $output;
    }

    private function getShortText($text, $max_length = 100, $more_symbol = '...') {
        if ( !strip_tags(html_entity_decode($text, ENT_QUOTES, 'UTF-8')) ) return $text;

        if ( mb_strlen($text,'UTF-8') > $max_length )
            $result = utf8_substr(strip_tags(html_entity_decode($text, ENT_QUOTES, 'UTF-8')), 0, $max_length) . $more_symbol;
        else
            $result = strip_tags(html_entity_decode($text, ENT_QUOTES, 'UTF-8'));

        return $result;
    }

    private function modelExists($model) {
        $file = DIR_APPLICATION . 'model/' . $model . '.php';

        return file_exists($file);
    }
}
