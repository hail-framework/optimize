# Hail Optimize
Use the memory extension cache:
- data storage in files, reduce file system IO
- data after complex operations


# Environment Variables
- HAIL_OPTIMIZE_ADAPTER
    - auto (default)
        - check order: yac, apcu, wincache, redis
    - yac
        - yac extension must be installed
    - apcu
        - apcu extension must be installed
    - wincache
        - wincache extension must be installed
    - redis
        - phpredis extension must be installed
        - HAIL_OPTIMIZE_REDIS must be defined
    - memory
        - saving data with PHP arrays
    - none
        - disable
- HAIL_OPTIMIZE_DELAY
    - The time interval between checking whether the cached file changes (seconds)
    - 0 means check every time you get data
    - Less than 0 means never check (not recommended)
    - if not defined, the default value is 5
- HAIL_OPTIMIZE_REDIS
    - redis configuration
    - unix:///var/run/redis/redis.sock?auth=password&select=0
    - tcp://127.0.0.1:6379?auth=password&select=0

# Example
```php
use Hail\Optimize\OptimizeTrait;

class Example
{
    use OptimizeTrait;
    
    private $folder;
    
    public function __construct(string $folder)
    {
        $this->folder = $folder;
    }
    
    public function dataReader(string $file)
    {
        return unserialize(file_get_contents($file));
    }
    
    public function get($name)
    {
        $file = $this->folder . DIRECTORY_SEPARATOR . $name . '.json';
        return $this->optimize()->load($file);
    }
    
    public function getSerializeData($name)
    {
        $file = $this->folder . DIRECTORY_SEPARATOR . $name . '.data';

        return $this->optimize()->load($file, [$this, 'dataReader']);
    }
}
```
