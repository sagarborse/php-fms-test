<?php

interface FolderInterface
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
   * @return string
   */
  public function getPath();
  
  /**
   * @param string $path
   *
   * @return $this
   */
  public function setPath($path);
}
