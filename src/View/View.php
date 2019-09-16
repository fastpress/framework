<?php declare(strict_types=1);
/**
 * Templating object
 *
 * PHP version 7.0
 *
 * @category   fastpress
 *
 * @author     https://github.com/samayo
 * @copyright  Copyright (c) samayo
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 *
 * @version    0.1.0
 */
namespace Fastpress\View;
/**
 * Templating object
 *
 * @category   fastpress
 *
 * @author     https://github.com/samayo
 */
class View
{
    private $app;
    private $data;
    private $view;
    private $block = [];
    private $layout = 'layout.html';


    public function __construct(array $conf, $app)
    {
        if (empty($conf)) {
            throw new \InvalidArgumentException(
                'template class requires atleast one runtime configuration'
            );
        }
        
        $this->app = $app;
        $this->conf = $conf;
    }

    public function render($view, array $vars = [])
    {
        $app = $this->app;
        if ($this->view === null) {
            extract($vars, EXTR_SKIP);
            if (file_exists($view = $this->conf['template']['views'] . $view)) {
                $this->view = $view;
                require $view;
            } else {
                throw new \Exception(sprintf(
                    "%s template does not exist in %s ",
            
                    $view,
            
                    $this->conf['template']['views'] 
                    ));
            }
        }
        return $this;
    }

    public function extend($layout)
    {
        $this->layout = $this->conf['template']['layout'] . $layout . '.html';
        return $this;
    }

    public function content($name)
    {
        if (array_key_exists($name, $this->block)) {
            return $this->data;
        }
    }


    public function layout($layout = null, array $vars = [])
    {
        $layout = $layout ? $layout : $this->layout;
        $app = $this->app;
        $this->layout = $this->conf['template']['layout'] . $layout;
        return $this->layout;
    }

    public function block($name)
    {
        $this->block[$name] = $name;
        ob_start();
    }

    public function endblock($name)
    {
        if (!array_key_exists($name, $this->block)) {
            throw new \Exception($name .' is unknown block');
        }
        
        $app = $this->app;
        $this->data = ob_get_contents();
        ob_end_clean();
 
        require $this->layout;
    }
}

   