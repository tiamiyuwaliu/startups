<?php
class FileController extends Controller {

    public function index() {
        $this->setTitle(l('file-manager'));
        $this->setActiveIconMenu('files');

        if ($val = $this->request->input('val')) {
            if (isset($val['upload'])) {
                if ($files = $this->request->inputFile('file')) {

                    $uploadedFiles = array();
                    foreach($files as $file) {
                        if (!$this->model('user')->canUpload()) {
                            return json_encode(array(
                                'type' => 'error',
                                'message' => l('file-upload-usage-limit')
                            ));
                        }
                        if (isImage($file)) {
                            if (!$this->model('user')->hasPermission('photo')){
                                return json_encode(array(
                                    'type' => 'error',
                                    'message' => l('you-are-not-allow-photo')
                                ));
                            }
                        } else {
                            if (!$this->model('user')->hasPermission('video')){
                                return json_encode(array(
                                    'type' => 'error',
                                    'message' => l('you-are-not-allow-video')
                                ));
                            }
                        }
                        $upload = new Uploader($file, (isImage($file)) ? 'image' : 'video');
                        (isImage($file)) ? $upload->setPath("files/images/".model('user')->authOwnerId.'/'.time().'/') : $upload->setPath('files/videos/'.model('user')->authOwnerId.'/');
                        if ($upload->passed()) {
                            if (isImage($file)) {
                                $result = $upload->resize()->result();
                                $val['file_name'] = str_replace('%w', 920, $result);
                                $val['resize_image'] = str_replace('%w', 200, $result);
                                $val['file_size'] = filesize(path($val['file_name']));
                                $val['file_type'] = 'image';

                            } else {
                                $val['file_type'] = 'video';
                                $val['file_name'] = $upload->uploadFile()->result();
                                $val['file_size'] = filesize(path($val['file_name']));
                                $val['resize_image'] = '';
                            }

                            $id = $this->model('file')->save($val);
                            $file = model('file')->find($id);
                            $file = array(
                                'id' => $file['id'],
                                'file_name' => $file['file_name'],
                                'thumbnail' => assetUrl($file['resize_image']),
                                'type' => $file['file_type'],
                                'file' => assetUrl($file['file_name'])
                                );
                            $uploadedFiles[] = $file;
                        } else {
                            return json_encode(array(
                                'type' => 'error',
                                'message' => $upload->getError()
                            ));
                        }
                    }

                    if (isset($val['upload_result'])) {

                        return json_encode(array(
                            'type' => 'function',
                            'value' => $val['upload_result'],
                            'content' => json_encode($uploadedFiles),
                            'message'=> l('upload-successful'),
                        ));
                    } else {
                        return json_encode(array(
                            'type' => 'reload',
                            'message'=> l('upload-successful'),
                        ));
                    }

                }

            }
            if (isset($val['action'])) {
                if(isset($val['files']) and !empty($val['files'])) {
                    foreach($val['files'] as $id) {
                        $this->model('file')->delete($id);
                    }
                    return json_encode(array(
                        'type' => 'reload',
                        'message' => l('files-deleted-successfully'),
                    ));
                }

            }

            if (isset($val['folder'])) {
                $id = $this->model('file')->addFolder($val);
                $folder = $this->model('file')->find($id);
                return json_encode(array(
                    'type' => 'reload-modal',
                    'message' => l('folder-created'),
                    'content' => '#addFolderModal'
                ));
            }

            if (isset($val['editfolder'])) {
                $this->model('file')->saveFolder($val);
                return json_encode(array(
                    'type' => 'reload-modal',
                    'message' => l('folder-edited'),
                    'content' => '#editFolderModal'
                ));
            }

        }


        if ($action = $this->request->input('action')) {
            switch($action) {
                case 'delete':
                    $this->defendDemo();
                    $id = $this->request->input('id');
                    $this->model('file')->delete($id);
                    return json_encode(array(
                        'type' => 'reload',
                        'message' => l('files-deleted-successfully'),
                    ));
                    break;
                case 'delete-folder':
                    $this->defendDemo();
                    $id = $this->request->input('id');
                    $folder = $this->model('file')->find($id);
                    $url = ($folder['folder_id']) ? url('files/'. $folder['folder_id']) : url('files');
                    $this->model('file')->delete($id);
                    return json_encode(array(
                        'type' => 'url',
                        'value' => $url,
                        'message' => l('folder-deleted-successfully'),
                    ));
                    break;
                case 'move':
                    $id = $this->request->input('file');
                    $folder = $this->request->input('folder');
                    $this->model('file')->move($id, $folder);
                    return json_encode(array(
                        'type' => 'function',
                        'message' => l('file-moved-successfully'),

                    ));
                    break;
            }
        }

        if ($google = $this->request->input('google')) {
            $fileId = $this->request->input('file_id');
            $fileName = $this->request->input('file_name');
            $fileSize = $this->request->input('file_size');
            $oAuthToken = $this->request->input('oauthToken');

            if (!$this->model('file')->validSelectedFile($fileName)) {
                return json_encode(array('status' => '0', 'message' => l('selected-file-not-supported')));
            }

            $getUrl = 'https://www.googleapis.com/drive/v2/files/' . $fileId . '?alt=media';
            $authHeader = 'Authorization: Bearer ' . $oAuthToken;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $getUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array($authHeader));
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            $data = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);

            $ext = get_file_extension($fileName);
            $fileName = md5($fileName.time()).'.'.$ext;
            $val = array();
            if (!$this->model('user')->canUpload()) {
                return json_encode(array(
                    'type' => 'error',
                    'message' => l('file-upload-usage-limit')
                ));
            }

            if (isImage($fileName)) {
                if (!$this->model('user')->hasPermission('photo')){
                    return json_encode(array(
                        'type' => 'error',
                        'message' => l('you-are-not-allow-photo')
                    ));
                }
                $tempFileDir = 'uploads/files/images/'.model('user')->authOwnerId.'/';
                if (!is_dir(path($tempFileDir))) {
                    @mkdir(path($tempFileDir), 0777, true);
                }
                $tempFile = $tempFileDir.$fileName;
                file_put_contents(path($tempFile), $data);
                $upload = new Uploader(path($tempFile), 'image', false, true);
                $upload->setPath("files/images/".model('user')->authOwnerId.'/'.time().'/');
                $result = $upload->resize()->result();
                $val['file_name'] = str_replace('%w', 920, $result);
                $val['resize_image'] = str_replace('%w', 200, $result);
                $val['file_size'] = $fileSize;
                $val['file_type'] = 'image';
                $val['folder_id'] = $this->request->input('folder_id');


            } else {
                if (!$this->model('user')->hasPermission('video')){
                    return json_encode(array(
                        'type' => 'error',
                        'message' => l('you-are-not-allow-video')
                    ));
                }
                //for videos mp4
                $tempFileDir = 'uploads/files/videos/'.model('user')->authOwnerId.'/';
                if (!is_dir(path($tempFileDir))) {
                    @mkdir(path($tempFileDir), 0777, true);
                }
                $tempFile = $tempFileDir.$fileName;
                file_put_contents(path($tempFile), $data);

                $val['file_type'] = 'video';
                $val['file_name'] = $tempFile;
                $val['file_size'] = $fileSize;
                $val['resize_image'] = '';
                $val['folder_id'] = $this->request->input('folder_id');
            }

            $id = $this->model('file')->save($val);
            return json_encode(array(
                'status' => 1,
                'message'=> l('upload-successful'),
                'content' => view('files/display', array('file' => model('filemanager')->find($id)))
            ));
        }

        if ($dropbox = $this->request->input('dropbox')) {
            $fileName = $this->request->input('file_name');
            $fileSize = $this->request->input('file_size');
            $fileLink = $this->request->input('file');
            if (!$this->model('file')->validSelectedFile($fileName)) {
                return json_encode(array('status' => '0', 'message' => l('selected-file-not-supported')));
            }

            if (!$this->model('user')->canUpload()) {
                return json_encode(array(
                    'type' => 'error',
                    'message' => l('file-upload-usage-limit')
                ));
            }

            $ext = get_file_extension($fileName);

            $dir = "uploads/files/file/".model('user')->authOwnerId.'/';
            if (!is_dir(path($dir))) mkdir(path($dir), 0777, true);
            $file = $dir.md5($fileName).'.'.$ext;
            getFileViaCurl($fileLink, $file);
            $val = array();
            if (isImage($fileName)) {
                if (!$this->model('user')->hasPermission('photo')){
                    return json_encode(array(
                        'type' => 'error',
                        'message' => l('you-are-not-allow-photo')
                    ));
                }
                $upload = new Uploader(path($file), 'image', false, true);
                $upload->setPath("files/images/".model('user')->authOwnerId.'/'.time().'/');
                $result = $upload->resize()->result();
                $val['file_name'] = str_replace('%w', 920, $result);
                $val['resize_image'] = str_replace('%w', 200, $result);
                $val['file_size'] = $fileSize;
                $val['file_type'] = 'image';
                $val['folder_id'] = $this->request->input('folder_id');

            } else {
                //for videos mp4
                if (!$this->model('user')->hasPermission('video')){
                    return json_encode(array(
                        'type' => 'error',
                        'message' => l('you-are-not-allow-video')
                    ));
                }
                $val['file_type'] = 'video';
                $val['file_name'] = $file;
                $val['file_size'] = $fileSize;
                $val['resize_image'] = '';
                $val['folder_id'] = $this->request->input('folder_id');
            }

            $id = $this->model('file')->save($val);
            return json_encode(array(
                'status' => 1,
                'message'=> l('upload-successful'),
                'content' => view('files/display', array('file' => model('file')->find($id)))
            ));
        }



        $offset = $this->request->input('offset', 0);

        $files = $this->model('file')->getFiles($offset, $this->request->segment(1), $this->request->input('type', 'all'));
        if ($paginate = $this->request->input('paginate')) {
            $content = '';
            foreach($files as $file) {
                $content .= view('files/display', array('file' => $file));
            }
            return json_encode(array(
                'offset' => $offset + 40,
                'content' => $content
            ));
        }
        return $this->render($this->view('files/index', array('files' => $files)), true);
    }

    public function load() {
        $offset = $this->request->input('offset', 0);

        $files = $this->model('file')->getFiles($offset, $this->request->segment(2), $this->request->input('type', 'all'));
        if ($paginate = $this->request->input('paginate')) {
            $content = '';
            foreach($files as $file) {
                $content .= view('files/display', array('file' => $file, 'load' => true));
            }
            return json_encode(array(
                'offset' => $offset + 40,
                'content' => $content
            ));
        }
        return $this->view('files/load', array('files' => $files));
    }
}