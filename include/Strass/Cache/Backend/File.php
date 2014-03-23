<?php

function strass_cache_backend_file_glob($cachedir, $prefix)
{
  $out = array();
  foreach(scandir($cachedir) as $entry) {
    if (strpos($entry, $prefix) === 0)
      array_push($out, $cachedir. '/'. $entry);
  }
  return $out;
}

class Strass_Cache_Backend_File extends Zend_Cache_Backend_File
{
    /**
     * Clean some cache records (protected method used for recursive stuff)
     *
     * Available modes are :
     * Zend_Cache::CLEANING_MODE_ALL (default)    => remove all cache entries ($tags is not used)
     * Zend_Cache::CLEANING_MODE_OLD              => remove too old cache entries ($tags is not used)
     * Zend_Cache::CLEANING_MODE_MATCHING_TAG     => remove cache entries matching all given tags
     *                                               ($tags can be an array of strings or a single string)
     * Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG => remove cache entries not {matching one of the given tags}
     *                                               ($tags can be an array of strings or a single string)
     * Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG => remove cache entries matching any given tags
     *                                               ($tags can be an array of strings or a single string)
     *
     * @param  string $dir  Directory to clean
     * @param  string $mode Clean mode
     * @param  array  $tags Array of tags
     * @throws Zend_Cache_Exception
     * @return boolean True if no problem
     */
    protected function _clean($dir, $mode = Zend_Cache::CLEANING_MODE_ALL, $tags = array())
    {
        if (!is_dir($dir)) {
            return false;
        }
        $result = true;
        $prefix = $this->_options['file_name_prefix'];
        $glob = @strass_cache_backend_file_glob($dir, $prefix . '--');
        if ($glob === false) {
            // On some systems it is impossible to distinguish between empty match and an error.
            return true;
        }
        foreach ($glob as $file)  {
            if (is_file($file)) {
                $fileName = basename($file);
                if ($this->_isMetadatasFile($fileName)) {
                    // in CLEANING_MODE_ALL, we drop anything, even remainings old metadatas files
                    if ($mode != Zend_Cache::CLEANING_MODE_ALL) {
                        continue;
                    }
                }
                $id = $this->_fileNameToId($fileName);
                $metadatas = $this->_getMetadatas($id);
                if ($metadatas === FALSE) {
                    $metadatas = array('expire' => 1, 'tags' => array());
                }
                switch ($mode) {
                    case Zend_Cache::CLEANING_MODE_ALL:
                        $res = $this->remove($id);
                        if (!$res) {
                            // in this case only, we accept a problem with the metadatas file drop
                            $res = $this->_remove($file);
                        }
                        $result = $result && $res;
                        break;
                    case Zend_Cache::CLEANING_MODE_OLD:
                        if (time() > $metadatas['expire']) {
                            $result = $this->remove($id) && $result;
                        }
                        break;
                    case Zend_Cache::CLEANING_MODE_MATCHING_TAG:
                        $matching = true;
                        foreach ($tags as $tag) {
                            if (!in_array($tag, $metadatas['tags'])) {
                                $matching = false;
                                break;
                            }
                        }
                        if ($matching) {
                            $result = $this->remove($id) && $result;
                        }
                        break;
                    case Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG:
                        $matching = false;
                        foreach ($tags as $tag) {
                            if (in_array($tag, $metadatas['tags'])) {
                                $matching = true;
                                break;
                            }
                        }
                        if (!$matching) {
                            $result = $this->remove($id) && $result;
                        }
                        break;
                    case Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG:
                        $matching = false;
                        foreach ($tags as $tag) {
                            if (in_array($tag, $metadatas['tags'])) {
                                $matching = true;
                                break;
                            }
                        }
                        if ($matching) {
                            $result = $this->remove($id) && $result;
                        }
                        break;
                    default:
                        Zend_Cache::throwException('Invalid mode for clean() method');
                        break;
                }
            }
            if ((is_dir($file)) and ($this->_options['hashed_directory_level']>0)) {
                // Recursive call
                $result = $this->_clean($file . DIRECTORY_SEPARATOR, $mode, $tags) && $result;
                if ($mode == Zend_Cache::CLEANING_MODE_ALL) {
                    // we try to drop the structure too
                    @rmdir($file);
                }
            }
        }
        return $result;
    }

    protected function _get($dir, $mode, $tags = array())
    {
        if (!is_dir($dir)) {
            return false;
        }
        $result = array();
        $prefix = $this->_options['file_name_prefix'];
        $glob = @strass_cache_backend_file_glob($dir, $prefix . '--');
        if ($glob === false) {
            // On some systems it is impossible to distinguish between empty match and an error.
            return array();
        }
        foreach ($glob as $file)  {
            if (is_file($file)) {
                $fileName = basename($file);
                $id = $this->_fileNameToId($fileName);
                $metadatas = $this->_getMetadatas($id);
                if ($metadatas === FALSE) {
                    continue;
                }
                if (time() > $metadatas['expire']) {
                    continue;
                }
                switch ($mode) {
                    case 'ids':
                        $result[] = $id;
                        break;
                    case 'tags':
                        $result = array_unique(array_merge($result, $metadatas['tags']));
                        break;
                    case 'matching':
                        $matching = true;
                        foreach ($tags as $tag) {
                            if (!in_array($tag, $metadatas['tags'])) {
                                $matching = false;
                                break;
                            }
                        }
                        if ($matching) {
                            $result[] = $id;
                        }
                        break;
                    case 'notMatching':
                        $matching = false;
                        foreach ($tags as $tag) {
                            if (in_array($tag, $metadatas['tags'])) {
                                $matching = true;
                                break;
                            }
                        }
                        if (!$matching) {
                            $result[] = $id;
                        }
                        break;
                    case 'matchingAny':
                        $matching = false;
                        foreach ($tags as $tag) {
                            if (in_array($tag, $metadatas['tags'])) {
                                $matching = true;
                                break;
                            }
                        }
                        if ($matching) {
                            $result[] = $id;
                        }
                        break;
                    default:
                        Zend_Cache::throwException('Invalid mode for _get() method');
                        break;
                }
            }
            if ((is_dir($file)) and ($this->_options['hashed_directory_level']>0)) {
                // Recursive call
                $recursiveRs =  $this->_get($file . DIRECTORY_SEPARATOR, $mode, $tags);
                if ($recursiveRs === false) {
                    $this->_log('Zend_Cache_Backend_File::_get() / recursive call : can\'t list entries of "'.$file.'"');
                } else {
                    $result = array_unique(array_merge($result, $recursiveRs));
                }
            }
        }
        return array_unique($result);
    }
}