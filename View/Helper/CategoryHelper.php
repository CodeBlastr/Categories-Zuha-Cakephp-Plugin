<?php

class CategoryHelper extends AppHelper {

	public $helpers = array('Html');

/**
 * @param View $View
 * @param array $settings
 */
	public function __construct(View $View, $settings = array()) {
		parent::__construct($View, $settings);

		if ($this->request->isPost()) {
			$this->_handlePost();
		}
	}

/**
 * @param string $type
 * @param array $params
 * @return array
 */
	public function find($type = 'first', $params = array()) {
		$this->Category = ClassRegistry::init('Categories.Category');
		return $this->Category->find($type, $params);
	}

/**
 * @return array
 */
	public function findThreaded() {
		$this->Category = ClassRegistry::init('Categories.Category');
		$threaded = $this->Category->find('threaded');
		$models = Set::extract('/Category/model', $this->Category->find('all', array('group' => array('Category.model'), 'fields' => array('Category.model'))));
		foreach ($models as $model) {
			foreach ($threaded as $thread) {
				if ($thread['Category']['model'] == $model) {
					$categories[$model][] = $thread;
					//$options[$model] = $this->Category->generateTreeList(array('Category.model' => $model), null, null, '--');
				}
			}
		}
		return $categories;
	}

/**
 * @param array $options
 * @return array
 */
	public function loadData($options = array()) {
		$this->Category = ClassRegistry::init('Categories.Category');
//		$joins = array(
//			array('table' => 'categorized',
//				'alias' => 'Categorized',
//				'type' => 'left',
//				'conditions' => array(
//					'Categorized.foreign_key = Classified.id'
//				)),
//			array('table' => 'categories',
//				'alias' => 'Category',
//				'type' => 'left',
//				'conditions' => array(
//					'Category.id = Categorized.category_id'
//				))
//		);
		$defaults = array('contain' => 'Categorized');
		$options = Set::merge($options, $defaults);
		$data = $this->Category->find('all', $options);
		return $data;
	}

/**
 * Returns list of top-level categories
 * @return array
 */
	public function displayList() {
		$Category = ClassRegistry::init('Categories.Category');
		return $Category->find('list', array('conditions' => array('Category.parent_id' => '')));
	}

/**
 * @param char $id
 * @return type
 */
	public function displayItems($id) {
		$Category = ClassRegistry::init('Categories.Category');
		return( $Category->view($id) );
	}

/**
 * @param array $categories
 * @param array $options
 * @return string
 */
	public function display($categories = array(), $options = array()) {
		$output = '';
		switch ($options['type']) {
			case ('selectForm'):
				$output .= $this->_selectForm($categories, $options);
				break;
			case ('ul'):
			default:
				$output .= $this->_recursiveUl($categories);
				break;
		}
		return $output;
	}

/**
 * @param array $array
 * @return string
 */
	protected function _recursiveUl($array) {
		$output = '';
		if (!empty($array)) {
			$output .= '<ul>';
			foreach ($array as $item) {
				$output .= '<li>';
				$output .= $item['Category']['name'];
				$output .= $this->_recursiveUl($item['children']);
				$output .= '</li>';
			}
			$output .= '</ul>';
		}
		return $output;
	}

/**
 * @param array $array
 * @param array $options
 * @return string
 */
	protected function _selectForm($array, $options) {
		$output = '';
		if (!empty($array)) {
			$output .= '<form method="post">';
			$output .= '<input type="hidden" name="data[Category][model]" value="' . $options['model'] . '" />';
			$output .= '<input type="hidden" name="data[Category][foreignKey]" value="' . $options['foreignKey'] . '" />';
			$output .= '<select name="data[Category][Category][0]">';
			$output .= '<option value="">- none -</option>';
			foreach ($array as $item) {
				$output .= '<option value="' . $item['Category']['id'] . '">';
				$output .= $item['Category']['name'];
				$output .= '</option>';
			}
			$output .= '</select>';
			$output .= '<input type="submit" value="save">';
			$output .= '</form>';
		}
		return $output;
	}

/**
 * @throws Exception
 */
	protected function _handlePost() {
		if (!empty($this->request->data['Category'])) {
			$categorized = array($this->request->data['Category']['model'] => array('id' => array($this->request->data['Category']['foreignKey'])));
			if (is_array($this->request->data['Category']['Category'])) {
				// this is for checkbox / multiselect submissions (multiple categories)
				$categorized['Category']['id'] = $this->request->data['Category']['Category'];
			} else {
				// this is for radio button submissions (one category)
				$categorized['Category']['id'][] = $this->request->data['Category']['Category'];
			}
			$this->Category = ClassRegistry::init('Categories.Category');
			try {
				$this->Category->categorized($categorized, $this->request->data['Category']['model']);
			} catch (Exception $e) {
				throw new Exception($e->getMessage());
			}
		}
	}

	public function getCatCrumb($catid, $sep = false, $links = true) {
		App::uses('Category', 'Categories.Model');
		$Category = new Category();
		$parents = $Category->getPath($catid);
		$html = "";
		if($links) {
			foreach ($parents as $i => $parent) {
				if($i < count($parents)-1) {
					$url = array(
							'plugin' => $this->request->params['plugin'],
							'controller' => $this->request->params['controller'],
							'action' => $this->request->params['action'],
							$parent['Category']['id']
					);
					$html .= $this->Html->link($parent['Category']['name'], $url);
					$html .= " ".$sep." ";
				}else {
					$html .= $parent['Category']['name'];
				}
			}
		}else {
			$html = implode(" ".$sep." ", Hash::extract($parents, '{n}.Category.name'));
		}
		return $html;
	}

}
