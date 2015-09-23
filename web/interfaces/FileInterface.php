<?php

interface FileInterface
{
  /**
   * @return string
   */
  public function getName();

  /**
   * @param string $name
   *
   * @return $this
   */
  public function setName($name);

  /**
   * @return int
   */
  public function getSize();

  /**
   * @param int $size
   *
   * @return $this
   */
  public function setSize($size);

  /**
   * @return DateTime
   */
  public function getCreatedTime();

  /**
   * @param DateTime $created
   *
   * @return $this
   */
  public function setCreatedTime($created);

  /**
   * @return DateTime
   */
  public function getModifiedTime();

  /**
   * @param DateTime $modified
   *
   * @return $this
   */
  public function setModifiedTime($modified);

  /**
   * @return FolderInterface
   */
  public function getParentFolder();

  /**
   * @param FolderInterface $parent
   *
   * @return $this
   */
  public function setParentFolder(FolderInterface $parent);

  /**
   * @return string
   */
  public function getPath();
}
