<?php
class WorkspaceController extends Controller {
    public function index() {
        $this->setTitle(l('manage-workspace'));

        if ($val = $this->request->input('val')) {
            if ($val['action'] == 'save-workspace') {
                $this->model('workspace')->save($val);
                return json_encode(array(
                    'type' => 'reload-modal',
                    'content' => '#workspaceModal'.$val['id'],
                    'message' => l('workspace-saved')
                ));
            } elseif($val['action'] == 'invite-user' and model('user')->canDoTeam()) {
                $member = $this->model('workspace')->invite($val);
                return json_encode(array(
                    'type' => 'function',
                    'value' => 'Timably.userInvited',
                    'message' => l('user-invited-success'),
                    'content' => array('content' => $this->view('workspace/member/display', array('user' => $member)), 'id' => $val['id'])
                ));
            } elseif ($val['action'] == 'new-workspace' and model('user')->canDoTeam()) {
                $this->model('workspace')->create($val);
                return json_encode(array(
                    'type' => 'reload-modal',
                    'message' => l('new-workspace-created'),
                    'content' => '#newWorkspaceModal',
                ));
            }
        }

        if ($action = $this->request->input('action')) {
            switch($action) {
                case 'resend-invite':
                    $id = $this->request->input('id');
                    $this->model('workspace')->sendInvitation($id);
                    return json_encode(array(
                        'type' => 'function',
                        'message' => l('workspace-invitation-sent')
                    ));
                    break;
                case 'delete-member':
                    $id = $this->request->input('id');
                    model('workspace')->deleteMember($id);
                    return json_encode(array(
                        'type' => 'function',
                        'value' => 'Timably.workspaceMemberDeleted',
                        'content' => $id,
                        'message' => l('member-deleted-success')
                    ));
                    break;
            }
        }
        $page = $this->request->segment(1, 'workspace');

        return $this->render($this->view('workspace/index', array('page' => $page)), true);
    }
}