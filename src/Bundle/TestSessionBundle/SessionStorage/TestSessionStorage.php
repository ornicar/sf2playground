<?php

namespace Bundle\TestSessionBundle\SessionStorage;
use Symfony\Components\HttpFoundation\SessionStorage\SessionStorageInterface;

class TestSessionStorage implements SessionStorageInterface
{
    protected $sessionData = array();
    protected $options;

    /**
     * Constructor.
     *
     * @param array $options  An associative array of options
     */
    public function __construct(array $options)
    {
        if (!isset($options['session_path']))
        {
            throw new \InvalidArgumentException('The "session_path" option is mandatory for the TestSessionStorage class.');
        }

        $this->options = array_merge(array(
            'session_id' => 'test'
        ), $options);
    }

    public function start()
    {
        // we read session data from temp file
        $file = $this->options['session_path'].DIRECTORY_SEPARATOR.$this->options['session_id'].'.session';
        $this->sessionData = file_exists($file) ? unserialize(file_get_contents($file)) : array();
    }

    public function persist()
    {
        $current_umask = umask(0000);
        if (!is_dir($this->options['session_path']))
        {
            mkdir($this->options['session_path'], 0777, true);
        }
        umask($current_umask);
        file_put_contents($this->options['session_path'].DIRECTORY_SEPARATOR.$this->options['session_id'].'.session', serialize($this->sessionData));
    }

    /**
     * Reads data from this storage.
     *
     * The preferred format for a key is directory style so naming conflicts can be avoided.
     *
     * @param string $key A unique key identifying your data
     *
     * @return mixed Data associated with the key
     */
    public function read($key, $default = null)
    {
        return array_key_exists($key, $this->sessionData) ? $this->sessionData[$key] : $default;
    }

    /**
     * Removes data from this storage.
     *
     * The preferred format for a key is directory style so naming conflicts can be avoided.
     *
     * @param  string $key  A unique key identifying your data
     *
     * @return mixed Data associated with the key
     */
    public function remove($key)
    {
        $retval = null;

        if (isset($this->sessionData[$key])) {
            $retval = $this->sessionData[$key];
            unset($this->sessionData[$key]);
        }

        return $retval;
    }

    /**
     * Writes data to this storage.
     *
     * The preferred format for a key is directory style so naming conflicts can be avoided.
     *
     * @param string $key   A unique key identifying your data
     * @param mixed  $data  Data associated with your key
     *
     */
    public function write($key, $data)
    {
        $this->sessionData[$key] = $data;
    }

    /**
     * Regenerates id that represents this storage.
     *
     * @param  boolean $destroy Destroy session when regenerating?
     *
     * @return boolean True if session regenerated, false if error
     *
     */
    public function regenerate($destroy = false)
    {
        if($destroy) {
            $this->sessionData = array();
        }

        return true;
    }
}
