<?php

class Main {

    private $fs;

    public function __construct(FileSystem $fs) 
    {
        date_default_timezone_set('GMT');
        $this->fs = $fs;
    }

    /**
     * @param $string
     * @return string
     */
    public function getMethodName($string)
    {
        $string = strtolower($string);
        $parts = explode('-', $string);
        $cmd = $parts[0];
        for ($i = 1; $i < count($parts); $i++) {
            $cmd .= ucfirst($parts[$i]);
        }
        return $cmd;
    }

    /**
     * @param string $methodName
     * @param array $params
     * @return mixed
     */
    public function handle($methodName, $params)
    {
        $parsedParams = $this->getParsedCommandLineParameters($params);
        return $this->$methodName($parsedParams);
    }

    /**
     * @param array $params
     * @return string
     */
    public function createRootFolder($params)
    {
        $folder = new Folder();
        $folder->setCreatedTime(new \DateTime());
        $folder = $this->assignParameters($folder, $params);

        try 
        {
            $folder = $this->fs->createRootFolder($folder);
            return 'Folder with id: '.$folder->getId().' created successfully';
        } 
        catch (\Exception $ex) 
        {
            return $ex->getMessage();
        }
    }


    /**
     * @param array $params
     * @return string
     */
    public function createRootFolderAndFile($params)
    {
        $folder = new Folder();
        $folder->setCreatedTime(new \DateTime());

        $file = new File();
        $file->setCreatedTime(new \DateTime());

        if (isset($params['folder-name'])) 
        {
            $folder->setName($params['folder-name']['value']);
        }
        if (isset($params['folder-path'])) 
        {
            $folder->setPath($params['folder-path']['value']);
        }

        if (isset($params['file-name'])) 
        {
            $file->setName($params['file-name']['value']);
        }

        if (isset($params['file-size'])) 
        {
            $file->setSize($params['file-size']['value']);
        }

        try 
        {
            $file = $this->fs->createFile($file, $folder);
            return 'Folder with id: '.$folder->getId().' and file with id: '.$file->getId().' created successfully';
        } 
        catch (\Exception $ex) 
        {
            return $ex->getMessage();
        }
    }


    /**
     * @param array $params
     * @return string
     */
    public function createRootAndChildFolder($params)
    {
        $parent = new Folder();
        $parent->setCreatedTime(new \DateTime());
        $child = new Folder();
        $child->setCreatedTime(new \DateTime())->setParentFolder($parent);


        if (isset($params['root-folder-path'])) {
            $parent->setPath($params['root-folder-path']['value']);
        }

        if (isset($params['root-folder-name'])) {
            $parent->setName($params['root-folder-name']['value']);
        }


        if (isset($params['child-folder-path'])) {
            $child->setPath($params['child-folder-path']['value']);
        }

        if (isset($params['child-folder-name'])) {
            $child->setName($params['child-folder-name']['value']);
        }

        try {
            $child = $this->fs->createFolder($child, $parent);
            return 'Root folder with id: '.$parent->getId().' and child folder with id: '.$child->getId().' created successfully';
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }


    /**
     * @param array $params
     * @return string
     */
    public function updateFile($params)
    {
        if (isset($params['id'])) {
            $file = $this->fs->loadFile((int) $params['id']['value']);
            if (!$file) {
                return 'File with ID '.$params['id']['value'].' doesnt exist in database';
            } else {
                unset($params['id']);
                $this->assignParameters($file, $params);
                try {
                    $this->fs->updateFile($file);
                    return 'File with id: '.$file->getId().' updated successfully at '.$file->getModifiedTime()->format('Y-m-d H:i:s');
                } catch (\Exception $ex) {
                    return $ex->getMessage();
                }
            }
        } else {
            return 'Existing file ID was not provided';
        }
    }


    /**
     * @param array $params
     * @return string
     */
    public function renameFile($params)
    {
        if (isset($params['id'])) {
            $file = $this->fs->loadFile((int) $params['id']['value']);
            if (!$file) {
                return 'File with ID '.$params['id']['value'].' doesnt exist in database';
            } else {
                if (isset($params['new-name'])) {
                    $this->fs->renameFile($file, $params['new-name']['value']);
                    return 'File with ID '.$file->getId().' successfully renamed';
                } else {
                    return 'The new name was not provided';
                }
            }
        } else {
            return 'Existing file ID was not provided';
        }
    }

    /**
     * @param array $params
     * @return string
     */
    public function renameFolder($params)
    {
        if (isset($params['id'])) {
            $folder = $this->fs->loadFolder((int) $params['id']['value']);
            if (!$folder) {
                return 'Folder with ID '.$params['id']['value'].' doesnt exist in database';
            } else {
                if (isset($params['new-name'])) {
                    $this->fs->renameFolder($folder, $params['new-name']['value']);
                    return 'Folder with ID '.$folder->getId().' successfully renamed';
                } else {
                    return 'The new name was not provided';
                }
            }
        } else {
            return 'Existing folder ID was not provided';
        }
    }


    /**
     * @param array $params
     * @return string
     */
    public function getFolderCount($params)
    {
        if (isset($params['id'])) {
            $folder = $this->fs->loadFolder((int) $params['id']['value']);
            if (!$folder) {
                return 'Folder with ID '.$params['id']['value'].' doesnt exist in database';
            } else {
                return 'In the current folder there are: '.$this->fs->getFolderCount($folder).' folders';
            }
        } else {
            return 'Existing folder ID was not provided';
        }
    }

    /**
     * @param array $params
     * @return string
     */
    public function getAllSubFolderCount($params)
    {
        if (isset($params['id'])) {
            $folder = $this->fs->loadFolder((int) $params['id']['value']);
            if (!$folder) {
                return 'Folder with ID '.$params['id']['value'].' doesnt exist in database';
            } else {
                return 'In the current folder there are: '.$this->fs->getAllSubFolderCount($folder).' folders (including all the subfolders)';
            }
        } else {
            return 'Existing folder ID was not provided';
        }
    }


    /**
     * @param array $params
     * @return string
     */
    public function getFolders($params)
    {
        if (isset($params['id'])) {
            $folder = $this->fs->loadFolder((int) $params['id']['value']);
            if (!$folder) {
                return 'Folder with ID '.$params['id']['value'].' doesnt exist in database';
            } else {
                $arr = $this->fs->getFolders($folder);
                return 'Var export of all the folders: '.var_export($arr, true);
            }
        } else {
            return 'Existing folder ID was not provided';
        }
    }

    /**
     * @param array $params
     * @return string
     */
    public function getFiles($params)
    {
        if (isset($params['id'])) {
            $folder = $this->fs->loadFolder((int) $params['id']['value']);
            if (!$folder) {
                return 'Folder with ID '.$params['id']['value'].' doesnt exist in database';
            } else {
                $arr = $this->fs->getFiles($folder);
                return 'Var export of all the files: '.var_export($arr, true);
            }
        } else {
            return 'Existing folder ID was not provided';
        }
    }


    /**
     * @param array $params
     * @return string
     */
    public function getFileCount($params)
    {
        if (isset($params['id'])) {
            $folder = $this->fs->loadFolder((int) $params['id']['value']);
            if (!$folder) {
                return 'Folder with ID '.$params['id']['value'].' doesnt exist in database';
            } else {
                return 'Files in current directory: '.$this->fs->getFileCount($folder);
            }
        } else {
            return 'Existing folder ID was not provided';
        }
    }

    /**
     * @param array $params
     * @return string
     */
    public function getTotalFileSizeInFolder($params)
    {
        if (isset($params['id'])) {
            $folder = $this->fs->loadFolder((int) $params['id']['value']);
            if (!$folder) {
                return 'Folder with ID '.$params['id']['value'].' doesnt exist in database';
            } else {
                return 'Size of the current directory excluding subdirectories: '.$this->fs->getTotalFileSizeInFolder($folder);
            }
        } else {
            return 'Existing folder ID was not provided';
        }
    }


    /**
     * @param array $params
     * @return string
     */
    public function getDirectorySize($params)
    {
        if (isset($params['id'])) {
            $folder = $this->fs->loadFolder((int) $params['id']['value']);
            if (!$folder) {
                return 'Folder with ID '.$params['id']['value'].' doesnt exist in database';
            } else {
                return 'Directory size (including subdirectories): '.$this->fs->getDirectorySize($folder);
            }
        } else {
            return 'Existing folder ID was not provided';
        }
    }





    /**
     * @param array $params
     * @return string
     */
    public function createFile($params)
    {
        if (isset($params['folder-id'])) {
            $folder = $this->fs->loadFolder($params['folder-id']['value']);
            if ($folder) {
                $file = new File();
                $file->setCreatedTime(new \DateTime());

                if (isset($params['file-name'])) {
                    $file->setName($params['file-name']['value']);
                }

                if (isset($params['file-size'])) {
                    $file->setSize($params['file-size']['value']);
                }
                try {
                    $file = $this->fs->createFile($file, $folder);
                    return 'New file with id: '.$file->getId().' created successfully in folder with id '.$folder->getId();
                } catch (\Exception $ex) {
                    return $ex->getMessage();
                }
            } else {
                return 'Specified Folder ID doesn\'t exist in database';
            }
        } else {
            return 'Folder ID was not specified';
        }
    }


    /**
     * @param $params
     * @return string
     */
    public function deleteFile($params)
    {
        if (isset($params['id'])) {

            $file = $this->fs->loadFile($params['id']['value']);
            if ($file) {
                $this->fs->deleteFile($file);
                return 'File with ID '.$params['id']['value'].' successfully deleted';
            } else {
                return 'File with ID '.$params['id']['value'].' does not exist in database';
            }
        } else {
            return 'File ID not specified';
        }
    }


    /**
     * @param array $params
     * @return string
     */
    public function deleteFolder($params)
    {
        if (isset($params['id'])) {
            $folder = $this->fs->loadFolder($params['id']['value']);
            if ($folder) {
                try {
                    $this->fs->deleteFolder($folder);
                    return 'Folder with id: '.$folder->getId().' and the associated files and subfolders successfully deleted';
                } catch (\Exception $ex) {
                    return $ex->getMessage();
                }
            } else {
                return 'Specified Folder ID doesn\'t exist in database';
            }
        } else {
            return 'Folder ID was not specified';
        }
    }


    /**
     * @param array $params
     * @return array
     */
    private function getParsedCommandLineParameters($params)
    {
        unset($params[1]);
        $parsedParams = array();
        foreach($params as $param) {
            if (preg_match('/^--(.*)=(.*)/si', $param, $matches)) {
                $parsedParams[$matches[1]] = array(
                    'methodName' => 'set'.ucfirst(strtolower($matches[1])),
                    'value'      => $matches[2]
                );
            }
        }
        return $parsedParams;
    }

    /**
     * @param Object $object
     * @param array $params
     * @return Object
     */
    public function assignParameters($object, $params)
    {
        foreach($params as $row) {
            if (method_exists($object, $row['methodName'])) {
                $object->$row['methodName']($row['value']);

            } else {
                //TODO - CLEAN THIS UP AT SOME POINT..
                echo chr(10).'WARNING: '.$row['methodName'].' doesn\'t exist. Skipping assignment'.chr(10);
            }
        }
        return $object;
    }



} 