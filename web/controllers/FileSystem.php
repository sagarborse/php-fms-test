<?php


class FileSystem implements FileSystemInterface {

    /**
     * @var \PDO
     */
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @param FileInterface   $file
     * @param FolderInterface $parent
     * @return FileInterface
     * @throws \Exception
     */
    public function createFile(FileInterface $file, FolderInterface $parent)
    {
        /**
         * @var $parent Folder
         * @var $file File
         */

        $file->setParentFolder($parent);
        if (!$parent->getId()) {
            $parent = $this->persistParentFolder($parent);
        }

        $fileErrors = $this->getValidateFileResult($file);
        if (in_array(true, $fileErrors)) {
            $this->reportErrors($fileErrors, 'creating file');
        }

        return $this->insertFileInDb($file);
    }

    /**
     * @param FileInterface $file
     *
     * @return FileInterface
     * @throws \InvalidArgumentException
     */
    public function updateFile(FileInterface $file)
    {
        /**
         * @var File $file
         */
        if (!$file->getId()) {
            throw new \InvalidArgumentException('File needs to be persisted, before it can be updated');
        }

        if ($this->fileExists($file->getPath(), $file->getId())) {
            throw new \InvalidArgumentException('Another file with the same path already exists in the database');
        }

        $file->setModifiedTime(new \DateTime());

        return $this->updateFileInDb($file);
    }

    /**
     * @param FileInterface $file
     * @param               $newName
     *
     * @return FileInterface
     */
    public function renameFile(FileInterface $file, $newName)
    {
        $file->setName($newName);
        $this->updateFile($file);
    }

    /**
     * @param FileInterface $file
     *
     * @return bool
     */
    public function deleteFile(FileInterface $file)
    {
        /**
         * @var File $file
         */
        $this->deleteFileFromDb($file->getId());
    }

    /**
     * @param FolderInterface $folder
     * @return FolderInterface
     * @throws \Exception
     */
    public function createRootFolder(FolderInterface $folder)
    {
        /**
         * @var $folder Folder
         */

        $folderErrors = $this->getValidateRootFolderResult($folder);

        if (in_array(true, $folderErrors)) {
            $this->reportErrors($folderErrors, 'creating root folder');
        }

        $query = 'INSERT INTO folders SET name = :name, created_time = :created_time, path = :path';
        $insert = $this->pdo->prepare($query);
        $insert->execute(array(
            'name'         => $folder->getName(),
            'created_time' => $folder->getCreatedTime()->format('Y-m-d H:i:s'),
            'path'         => $folder->getPath()
        ));

        $folder->setId($this->pdo->lastInsertId());

        return $folder;
    }

    /**
     * @param FolderInterface $folder
     * @param FolderInterface $parent
     * @return FolderInterface|void
     * @throws \Exception
     */
    public function createFolder(FolderInterface $folder, FolderInterface $parent)
    {
        /**
         * @var $folder Folder
         * @var $parent Folder
         */

        $folderErrors = $this->getValidateFolderResult($folder, $parent);

        if (in_array(true, $folderErrors)) {
            $this->reportErrors($folderErrors, 'creating folder');
        }

        if (!$parent->getId()) {
            $parent = $this->persistParentFolder($parent);
        }

        $query = 'INSERT INTO folders SET name = :name,
        created_time = :created_time, path = :path, parent_id = :parent_id';

        $insert = $this->pdo->prepare($query);
        $insert->execute(array(
            'name'         => $folder->getName(),
            'created_time' => $folder->getCreatedTime()->format('Y-m-d H:i:s'),
            'path'         => $folder->getPath(),
            'parent_id'    => $parent->getId()
        ));

        $folder->setId($this->pdo->lastInsertId());
        return $folder;
    }

    /**
     * @param FolderInterface $folder
     *
     * @return bool
     */
    public function deleteFolder(FolderInterface $folder)
    {
        /**
         * @var Folder $folder
         */
        return $this->deleteFolderFromDb($folder->getId());
    }

    /**
     * @param FolderInterface $folder
     * @param                 $newName
     *
     * @return FolderInterface
     */
    public function renameFolder(FolderInterface $folder, $newName)
    {
        $folder->setName($newName);
        $folder = $this->updateFolderInDb($folder);
        return $folder;
    }

    /**
     * @param FolderInterface $folder
     *
     * @return int
     */
    public function getFolderCount(FolderInterface $folder)
    {
        return $this->getChildrenFolderCount($folder);
    }

    /**
     * This returns all the subdirectory count.
     *
     * @param FolderInterface $folder
     *
     * @return int
     */
    public function getAllSubFolderCount(FolderInterface $folder)
    {
        return $this->getChildrenFolderCount($folder, 0, true);
    }

    /**
     * Get's file count in the current directory only
     *
     * @param FolderInterface $folder
     * @return int
     */
    public function getFileCount(FolderInterface $folder)
    {
        /**
         * @var Folder $folder
         */
        $query = 'select count(*) as total FROM files where folder_id = :id';
        $sql = $this->pdo->prepare($query);
        $sql->execute(array('id' => $folder->getId()));
        $row = $sql->fetch(\PDO::FETCH_ASSOC);

        return $row['total'];
    }

    /**
     * Get's the total of all files in all subdirectories of the folder specified
     *
     * @param FolderInterface $folder
     *
     * @return int
     */
    public function getDirectorySize(FolderInterface $folder)
    {
        /**
         * @var Folder $folder
         */
        $directories = $this->getAllSubFolders($folder);
        $directories[] = $folder;
        $totalSize = 0;
        foreach($directories as $directory) {
            $totalSize += $this->getTotalFileSizeInFolder($directory);
        }
        return $totalSize;
    }

    /**
     * Gets subfolders from the folder specified.
     *
     * @param FolderInterface $folder
     *
     * @return FolderInterface[]
     */
    public function getFolders(FolderInterface $folder)
    {
        /**
         * @var Folder $folder
         */
        $query = 'select id FROM folders where parent_id = :id';
        $sql = $this->pdo->prepare($query);
        $sql->execute(array('id' => $folder->getId()));
        $arr = array();
        while($row = $sql->fetch(\PDO::FETCH_ASSOC)) {
            $subFolder = $this->loadFolder($row['id']);
            if ($subFolder) {
                $arr[] = $subFolder;
            }
        }
        return $arr;
    }

    /**
     * @param FolderInterface $folder
     *
     * @return FileInterface[]
     */
    public function getFiles(FolderInterface $folder)
    {
        /**
         * @var Folder $folder
         */
        $query = 'select id FROM files where folder_id = :id';
        $sql = $this->pdo->prepare($query);
        $sql->execute(array('id' => $folder->getId()));
        $arr = array();
        while($row = $sql->fetch(\PDO::FETCH_ASSOC)) {
            $file = $this->loadFile($row['id']);
            if ($file) {
                $arr[] = $file;
            }
        }
        return $arr;
    }

    /**
     * @param string $path
     * @return bool
     */
    private function folderExists($path)
    {
        $existing = $this->pdo->prepare('SELECT id FROM folders where path = :path');
        $existing->execute(array('path' => $path));
        return (bool) $existing->rowCount();
    }

    /**
     * @param string $path
     * @param null|int $id
     * @return bool
     */
    private function fileExists($path, $id = null)
    {
        $sql = 'SELECT id FROM files WHERE path = :path';
        $params = array('path' => $path);
        if ($id) {
            $sql .= ' AND id != :id';
            $params['id'] = $id;
        }
        $existing = $this->pdo->prepare($sql);
        $existing->execute($params);
        return (bool) $existing->rowCount();
    }


    /**
     * @param array $check List of errors where key is the issue, and value is wether it happened or not
     * @param string $action
     * @throws \InvalidArgumentException
     */
    private function reportErrors($check, $action)
    {
        $errors = array_keys(
            array_filter($check, function($element) {return $element;})
        );
        $errorMessage = 'The following problems occurred: '.implode(', ', $errors).' when '.$action;
        throw new \InvalidArgumentException($errorMessage);
    }

    /**
     * @param FileInterface $file
     * @return FileInterface
     * @throws \Exception
     */
    public function insertFileInDb(FileInterface $file)
    {
        /**
         * @var $file File
         */

        $insert = $this->pdo->prepare('INSERT INTO files SET
        name = :name,
        size = :size,
        created_time = :created_time,
        path = :path,
        folder_id = :folder_id');

        try {
            $insert->execute(array(
                'name'         => $file->getName(),
                'size'         => $file->getSize(),
                'created_time' => $file->getCreatedTime()->format('Y-m-d H:i:s'),
                'path'         => $file->getPath(),
                'folder_id'    => $file->getParentFolder()->getId(),
            ));
        } catch (\Exception $ex) {
            throw new \Exception('Inserting file failed');
        }

        $file->setId($this->pdo->lastInsertId());
        return $file;
    }


    /**
     * @param FileInterface $file
     * @return FileInterface
     * @throws \Exception
     */
    public function updateFileInDb(FileInterface $file)
    {
        /**
         * @var $file File
         */

        $update = $this->pdo->prepare('UPDATE files SET
        name = :name,
        size = :size,
        modified_time = :modified_time,
        path = :path,
        folder_id = :folder_id
        WHERE id = :id');

        $modified_time = $file->getModifiedTime() ? $file->getModifiedTime()->format('Y-m-d H:i:s') : null;

        try {
            $update->execute(array(
                'name'          => $file->getName(),
                'size'          => $file->getSize(),
                'modified_time' => $modified_time,
                'path'          => $file->getPath(),
                'folder_id'     => $file->getParentFolder()->getId(),
                'id'            => $file->getId(),
            ));
        } catch (\Exception $ex) {
            throw new \Exception('Updating file failed');
        }

        return $file;
    }


    /**
     * @param FolderInterface $folder
     * @return Folder
     * @throws \Exception
     */
    public function updateFolderInDb(FolderInterface $folder)
    {
        /**
         * @var $folder Folder
         */
        $query = 'UPDATE folders SET name = :name, path = :path, parent_id = :parent_id WHERE id = :id';
        $update = $this->pdo->prepare($query);
        $folder_id = $folder->getParentFolder() ? $folder->getParentFolder()->getId() : null;
        try {
            $update->execute(array(
                'name'          => $folder->getName(),
                'path'          => $folder->getPath(),
                'parent_id'     => $folder_id,
                'id'            => $folder->getId(),
            ));
        } catch (\Exception $ex) {
            throw new \Exception('Updating folder failed');
        }

        return $folder;
    }


    /**
     * @param $id
     * @return Folder|null
     */
    public function loadFolder($id)
    {
        $query = 'SELECT id, parent_id, name, created_time, path FROM folders WHERE id = :id';
        $sql = $this->pdo->prepare($query);
        $sql->execute(array('id' => $id));

        if (!$sql->rowCount()) {
            return null;
        }

        $row = $sql->fetch(\PDO::FETCH_ASSOC);

        $folder = new Folder();
        $folder->setCreatedTime(new \DateTime($row['created_time']))
            ->setName($row['name'])
            ->setPath($row['path'])
            ->setId($row['id']);

        if ($row['parent_id']) {
            $folder->setParentFolder($this->loadFolder($row['parent_id']));
        }

        return $folder;
    }


    /**
     * @param int $id
     * @return File|null
     */
    public function loadFile($id)
    {
        $query = 'SELECT id, name, size, created_time, modified_time, folder_id, path FROM files WHERE id = :id';
        $sql = $this->pdo->prepare($query);
        $sql->execute(array('id' => $id));

        if (!$sql->rowCount()) {
            return null;
        }

        $row = $sql->fetch(\PDO::FETCH_ASSOC);

        $file = new File();
        $file->setId($row['id'])
            ->setName($row['name'])
            ->setSize($row['size'])
            ->setCreatedTime(new \DateTime($row['created_time']))
            ->setPath($row['path'])
            ->setParentFolder($this->loadFolder($row['folder_id']));

        return $file;
    }

    /**
     * @param int|null $id
     * @throws \Exception
     */
    public function deleteFileFromDb($id)
    {
        if ($id) {
            try {
                $sql = $this->pdo->prepare('DELETE FROM files WHERE id = :id');
                $sql->execute(array('id' => $id));
            } catch (\Exception $ex) {
                throw new \Exception('Could not delete file from database');
            }
        }
    }


    /**
     * @param int|null $id
     *
     * @return bool
     */
    public function deleteFolderFromDb($id)
    {
        if ($id) {
            try {
                $sql = $this->pdo->prepare('DELETE FROM folders WHERE id = :id');
                $sql->execute(array('id' => $id));
            } catch (\Exception $ex) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param FolderInterface $folder
     * @param int $total
     * @param bool $recursive
     * @return int
     */
    public function getChildrenFolderCount(FolderInterface $folder, $total = 0, $recursive = false)
    {
        /**
         * @var Folder $folder
         */
        $sql = $this->pdo->prepare('select id FROM folders where parent_id = :id');
        $sql->execute(array('id' => $folder->getId()));
        while($row = $sql->fetch(\PDO::FETCH_ASSOC)) {
            $total++;
            if ($recursive) {
                $subFolder = $this->loadFolder($row['id']);
                if ($subFolder) {
                    $total = $this->getChildrenFolderCount($subFolder, $total, $recursive);
                }
            }
        }
        return $total;
    }

    /**
     * @param FolderInterface $folder
     * @param FolderInterface[] $foldersLoaded
     * @return FolderInterface[]
     */
    public function getAllSubFolders(FolderInterface $folder, $foldersLoaded = array())
    {
        /**
         * @var Folder $folder
         */
        $sql = $this->pdo->prepare('select id FROM folders where parent_id = :id');
        $sql->execute(array('id' => $folder->getId()));
        while($row = $sql->fetch(\PDO::FETCH_ASSOC)) {
            $subFolder = $this->loadFolder($row['id']);
            if ($subFolder) {
                $foldersLoaded[] = $subFolder;
                $foldersLoaded = $this->getAllSubFolders($subFolder, $foldersLoaded);
            }
        }
        return $foldersLoaded;
    }


    /**
     * @param FolderInterface $folder
     * @return int
     */
    public function getTotalFileSizeInFolder(FolderInterface $folder)
    {
        /**
         * @var Folder $folder
         */
        $query = 'SELECT SUM(size) as total FROM files where folder_id = :folder_id';
        $sql = $this->pdo->prepare($query);
        $sql->execute(array('folder_id' => $folder->getId()));
        $row = $sql->fetch(\PDO::FETCH_ASSOC);
        return (int) $row['total'];
    }

    /**
     * Persists parent folder, which might be root folder or subfolder
     *
     * @param FolderInterface $parent
     * @return FolderInterface
     */
    private function persistParentFolder(FolderInterface $parent)
    {
        /**
         * @var Folder $parent
         */
        if (!$parent->getParentFolder()) {
            //Its a root folder
            $parent = $this->createRootFolder($parent);
        } else {
            //It's a subfolder
            $parent = $this->createFolder($parent, $parent->getParentFolder());
        }
        return $parent;
    }

    /**
     * @param FileInterface $file
     * @return array
     */
    private function getValidateFileResult(FileInterface $file)
    {
        $check = array(
            'File name not specified'    => !$file->getName(),
            'Filesize not specified'     => is_null($file->getSize()),
            'Created time not specified' => !$file->getCreatedTime(),
            'File path already exists'   => $this->fileExists($file->getPath())
        );
        return $check;
    }


    /**
     * @param FolderInterface $folder
     * @return array
     */
    private function getValidateRootFolderResult(FolderInterface $folder)
    {
        $check = array(
            'Folder name not specified'  => !$folder->getName(),
            'Path not specified'         => !$folder->getPath(),
            'Created time not specified' => !$folder->getCreatedTime(),
        );

        if ($folder->getPath()) {
            $key = 'Folder with a path '.$folder->getPath().' has been already created';
            $check[$key] = $this->folderExists($folder->getPath());
        }

        return $check;
    }


    /**
     * @param FolderInterface $folder
     * @param FolderInterface $parent
     * @return array
     */
    public function getValidateFolderResult(FolderInterface $folder, FolderInterface $parent)
    {
        $check = array(
            'Child folder name not specified'          => !$folder->getName(),
            'Child folder path not specified'          => !$folder->getPath(),
            'Child folder created time not specified'  => !$folder->getCreatedTime(),
            'Parent folder name not specified'         => !$parent->getName(),
            'Parent folder path not specified'         => !$parent->getPath(),
            'Parent folder created time not specified' => !$parent->getCreatedTime(),
        );

        if ($folder->getPath()) {
            $key = 'Folder with a path '.$folder->getPath().' has been already created';
            $check[$key] = $this->folderExists($folder->getPath());
        }

        return $check;
    }

} 