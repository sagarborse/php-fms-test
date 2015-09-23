<?php

class Folder implements FolderInterface {

    private $name;
    private $createdTime;
    private $path;
    private $id;
    private $parentFolder;

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
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     *
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = (string) $path;
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

    public function setParentFolder($parentFolder)
    {
        $this->parentFolder = $parentFolder;
    }

    public function getParentFolder()
    {
        return $this->parentFolder;
    }

} 