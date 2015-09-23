<?php


class File implements  FileInterface {

    private $name;
    private $size;
    private $createdTime;
    private $modifiedTime;
    private $parentFolder;
    private $id;
    private $path;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = (string) $name;
        return $this;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param int $size
     *
     * @return $this
     */
    public function setSize($size)
    {
        $this->size = (int) $size;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedTime()
    {
        return $this->createdTime;
    }

    /**
     * @param \DateTime $created
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setCreatedTime($created)
    {
        if (!$created instanceof \DateTime) {
            throw new \InvalidArgumentException('Created time must be an instance of DateTime object');
        }
        $this->createdTime = $created;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getModifiedTime()
    {
        return $this->modifiedTime;
    }

    /**
     * @param \DateTime $modified
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setModifiedTime($modified)
    {
        if (!$modified instanceof \DateTime) {
            throw new \InvalidArgumentException('Modified time must be an instance of DateTime object');
        }
        $this->modifiedTime = $modified;
        return $this;
    }

    /**
     * @return FolderInterface
     */
    public function getParentFolder()
    {
        return $this->parentFolder;
    }

    /**
     * @param FolderInterface $parent
     * @return $this
     */
    public function setParentFolder(FolderInterface $parent)
    {
        $this->parentFolder = $parent;
        return $this;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        $this->path = $this->name && $this->parentFolder ?
            $this->getParentFolder()->getPath().DIRECTORY_SEPARATOR.$this->getName() : null;
        return $this->path;
    }

    /**
     * @param string|null $path
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @param int|null $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

} 