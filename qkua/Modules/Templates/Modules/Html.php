<?php namespace Qk\Modules\Templates\Modules;

class Html{
    public function init($data,$i){

        $i = isset($data['key']) ? $data['key'] : 'ls'.round(100,999);

        if (strpos($data['html'], '<' . '?') !== false) {
            ob_start();
            eval('?' . '>' . $data['html']);
            $text = ob_get_contents();
            ob_end_clean();
        }else{
            $text = $data['html'];
        }
        
        return '<div id="html-box-'.$i.'" class="html-box">'.$text.'</div>';
    }
}