<?php
/**
 * Created by JetBrains PhpStorm.
 * User: yiqing
 * Date: 12-9-21
 * Time: 下午8:49
 * To change this template use File | Settings | File Templates.
 */
class ELiquidViewRenderer extends CApplicationComponent implements IViewRenderer {
    /**
     * @var string
     */
    public $fileExtension='.tpl';
    /**
     * @var int
     */
    public $cacheDirPermission=0755;

    /**
     * @var string path alias of the directory where the Liquid.class.php file can be found.
     */
    public $liquidDir = 'application.vendors.liquid';

    /**
     * @var array
     * -----------------------------------------------------------------
     * default : array('cache' => 'file', 'cache_dir' => PROTECTED_PATH.'/runtime/liquid/cache/');
     * array('cache' => 'apc');
     * -----------------------------------------------------------------
     */
    public $cache ;

    /**
     * @var LiquidTemplate
     */
    private $liquid ;

    /**
     * Component initialization
     */
    function init(){
        if(!defined('LIQUID_INCLUDE_ALLOW_EXT')){
            define('LIQUID_INCLUDE_ALLOW_EXT', true);
        }
        /*
        define('LIQUID_INCLUDE_SUFFIX', 'tpl');
        define('LIQUID_INCLUDE_PREFIX', '');
         */

        Yii::import('application.vendors.*');

        //....................................................................................
        // Unregister Yii autoloader
        spl_autoload_unregister(array('YiiBase','autoload'));
        // Register Liquid autoloader
        require_once(Yii::getPathOfAlias($this->liquidDir). DIRECTORY_SEPARATOR . 'Liquid.class.php');
        // Add Yii autoloader again
        spl_autoload_register(array('YiiBase','autoload'));
        //....................................................................................

        // we don't need the template dir !
        $this->liquid = new  LiquidTemplate();

        if(is_null($this->cache)){
          $cacheDir = Yii::app()->getRuntimePath().'/liquid/cache/';
            // create compiled directory if not exists
            if(!file_exists($cacheDir)){
                mkdir($cacheDir, $this->cacheDirPermission, true);
            }
            $this->cache = array('cache' => 'file', 'cache_dir' => $cacheDir);
        }
        $this->liquid->setCache($this->cache);
    }

    /**
     * Renders a view file.
     * @param CBaseController $context the controller or widget who is rendering the view file.
     * @param string $sourceFile
     * @param mixed $data the data to be passed to the view
     * @param boolean $return whether the rendering result should be returned
     * @throws CException
     * @internal param string $file the view file path
     * @return mixed the rendering result, or null if the rendering result is not needed.
     */
    public function renderFile($context,$sourceFile,$data,$return) {
        // current controller properties will be accessible as {this.property}
        $data['this'] = $context;
        $data['Yii'] = Yii::app();
        // time and memory information
        $data['TIME'] = sprintf('%0.5f',Yii::getLogger()->getExecutionTime());
        $data['MEMORY'] = round(Yii::getLogger()->getMemoryUsage()/(1024*1024),2).' MB';

        // check if view file exists
        if(!is_file($sourceFile) || ($file=realpath($sourceFile))===false){
            throw new CException(Yii::t('app','View file "{file}" does not exist.', array('{file}'=>$sourceFile)));
        }

        $this->liquid->parse(file_get_contents($sourceFile));

        //render or return
        if($return){
            return  $this->liquid->render($data);
        }else{
            echo $this->liquid->render($data);
        }
    }
}