<?php
class Exercise_ReplyController extends Tri_Controller_Action
{
    public function init()
    {
        parent::init();
        $this->view->title = "Exercise reply";
    }

    public function indexAction()
    {
        $id       = Zend_Filter::filterStatic($this->_getParam('id'), 'int');
        $identity = Zend_Auth::getInstance()->getIdentity();
        $message  = Exercise_Model_Reply::isDisabled($identity->id, $id);
        
        if ($message) {
            $this->_helper->_flashMessenger->addMessage($message);
            $this->_redirect('/exercise/reply/view/exerciseId/'.$id);
        }

        $exercise         = new Tri_Db_Table('exercise');
        $exerciseQuestion = new Tri_Db_Table('exercise_question');
        $exerciseNote     = new Tri_Db_Table('exercise_note');

        $exerciseNote->createRow(array('user_id' => $identity->id,
                                       'exercise_id' => $id,
                                       'note' => 0))->save();

        $row = $exercise->fetchRow(array('id = ?' => $id));

        if ($row) {
            $where = array('exercise_id = ?' => $row->id, 'status = ?' => 'active');
            $this->view->questions = $exerciseQuestion->fetchAll($where);
            $this->view->exercise  = $row;
        } else {
            $this->_helper->_flashMessenger->addMessage('Error');
            $this->_redirect('/exercise');
        }
    }

    public function saveAction()
    {
        if ($this->_request->isPost()) {
            $exerciseNote   = new Tri_Db_Table('exercise_note');
            $exerciseAnswer = new Tri_Db_Table('exercise_answer');
            $identity       = Zend_Auth::getInstance()->getIdentity();
            $params         = $this->_getAllParams();
            $data           = array();

            $note = $exerciseNote->fetchRow(array('user_id = ?' => $identity->id),
                                            'id DESC');
                              
            if (isset($params['option'])) {
                foreach ($params['option'] as $option) {
                    $data['exercise_note_id']   = $note->id;
                    $data['exercise_option_id'] = $option;

                    $exerciseAnswer->createRow($data)->save();
                }
            }

            $note->note = Exercise_Model_Reply::sumNote($note->id);
            $note->save();

            $this->_redirect('/exercise/reply/view/id/' . $note->id);
        } else {
            $this->_helper->_flashMessenger->addMessage('Error');
            $this->_redirect('/exercise');
        }
    }

    public function viewAction()
    {
        $id = Zend_Filter::filterStatic($this->_getParam('id'), 'int');
        $exerciseId = Zend_Filter::filterStatic($this->_getParam('exerciseId'), 'int');
        $identity         = Zend_Auth::getInstance()->getIdentity();
        $exercise         = new Tri_Db_Table('exercise');
        $exerciseQuestion = new Tri_Db_Table('exercise_question');
        $exerciseNote     = new Tri_Db_Table('exercise_note');
        $exerciseAnswer   = new Tri_Db_Table('exercise_answer');

        if ($id) {
            $note = $exerciseNote->fetchRow(array('id = ?' => $id));
        } elseif ($exerciseId) {
            $note = $exerciseNote->fetchRow(array('exercise_id = ?' => $exerciseId), 'id DESC');
        }

        if ($note) {
            $row = $exercise->fetchRow(array('id = ?' => $note->exercise_id));

            if ($row) {
                $where = array('exercise_id = ?' => $row->id, 'status = ?' => 'active');
                $this->view->questions = $exerciseQuestion->fetchAll($where);
                $this->view->exercise  = $row;
                $this->view->answers   = $exerciseAnswer->fetchAll(array('exercise_note_id = ?' => $note->id));
                $this->view->note      = $note;

                $whereNote = array('exercise_id = ?' => $note->exercise_id,
                                   'id <> ?' => $note->id);
                $this->view->notes = $exerciseNote->fetchAll($whereNote);
            } else {
                $this->_helper->_flashMessenger->addMessage('Error');
                $this->_redirect('/exercise');
            }
        } else {
            $this->_helper->_flashMessenger->addMessage('Error');
            $this->_redirect('/exercise');
        }
    }
}