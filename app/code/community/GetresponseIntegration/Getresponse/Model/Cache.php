<?php


class GetresponseIntegration_Getresponse_Model_Cache
{
    const TAG = 'getresponse';

    const DEFAULT_TTL = 600;

    /** @var Zend_Cache_Core */
    protected $cache;

    public function __construct()
    {
        $this->cache = Mage::app()->getCache();
        //$this->cache->setOption('automatic_serialization', true);
    }

    /**
     * @param string $value
     * @param string $key
     */
    public function save($value, $key)
    {
        $this->cache->save(serialize($value), $key, array(self::TAG), self::DEFAULT_TTL);
    }

    /**
     * @param string $key
     * @return false|mixed
     */
    public function load($key)
    {
        $data = $this->cache->load($key);
        if (false !== $data) {
            return unserialize($data);
        }
        return false;
    }

    /**
     * @param string $key
     */
    public function remove($key)
    {
        $this->cache->remove($key);
    }

    public function clean()
    {
        $this->cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array(self::TAG));
    }

}