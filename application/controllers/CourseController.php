<?php
/**
 * Trilhas - Learning Management System
 * Copyright (C) 2005-2010  Preceptor Educação a Distância Ltda. <http://www.preceptoead.com.br>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @category   Application
 * @package    Application_Controller
 * @copyright  Copyright (C) 2005-2010  Preceptor Educação a Distância Ltda. <http://www.preceptoead.com.br>
 * @license    http://www.gnu.org/licenses/  GNU GPL
 */
class CourseController extends Tri_Controller_Action
{
    public function init()
    {
        parent::init();
        $this->view->title = "Course";
    }

    public function viewAction()
    {
        $id = Zend_Filter::filterStatic($this->_getParam('id'), 'int');

        if ($id) {
            $course    = new Tri_Db_Table('course');
            $classroom = new Tri_Db_Table('classroom');

            $this->view->data = $course->find($id)->current();
            $where = array('course_id = ?' => $id, 
                           'status = ?' => 'open',
                           'end >= ? OR end IS NULL' => date('Y-m-d'));
            $this->view->classroom = $classroom->fetchAll($where, 'begin');
			$this->view->selectionProcess = SelectionProcess_Model_SelectionProcess::getAvailableProcessByCourse($id);
        }
    }

    public function widgetAction()
    {
        $course   = new Tri_Db_Table('course');
        $page     = Zend_Filter::filterStatic($this->_getParam('page'), 'int');
        $form     = new Application_Form_Login();
        $select   = $course->select()
                           ->where('status = ?', 'active')
                           ->order(array('name', 'category'));

        $paginator = new Tri_Paginator($select, $page);
        $this->view->data = $paginator->getResult();
    }

    public function dashboardAction()
    {
        $identity  = Zend_Auth::getInstance()->getIdentity();
        $courses   = Application_Model_Classroom::getAllByUser($identity->id);
        $finalized = Application_Model_Classroom::getFinalizedByUser($identity->id);

        $this->view->data      = $courses;
        $this->view->finalized = $finalized;
        $this->view->user      = $identity;
    }
}
