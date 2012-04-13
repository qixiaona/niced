<?php
/**
 * @desc layout manager class file
 * @author nana
 * @date 2011
 *
 */
class LayoutManager {

    public function __construct() { error_log("LayoutManager cannot be called as an instance"); exit; }

   

    /**
     * @desc Add an external script file to html
     */
    public static function AddCSS($path, $media = "screen") {
        self::data()->append("script_css", array(
                                          "path" => $path,
                                          "media" => $media
                                          ));
    }

    /**
	 * @desc add css file to html
	 */
    
    public static function AddCSS_IE( $path,  $ie_version = '', $media = "screen") {
        self::data()->append("script_css_ie", array(
                                             "path" => $path,
                                             "media" => $media,
                                             "ie_version" => $ie_version
                                             ));
    }

    public static function AddScript($path, $type="text/javascript") {
        if (strpos($path, 'http://') !== FALSE) {
            return self::AddExternalScript($path, $type);
        }

        self::data()->append("script_js", array(
                                         "path" => $path,
                                         "type" => $type
                                         ));

    }

	public static function loadJs($path, $filename) {
		echo '<script type="text/javascript" src="'.rtrim($path, '/').'/'.ltrim($filename, '/')."?".SC::get('board_config.version').'"></script>';
	}

	public static function loadCss($path, $filename) {
		echo '<link rel="stylesheet" type="text/css" media="all" href="'.rtrim($path, '/').'/'.ltrim($filename, '/')."?".SC::get('board_config.version').'" />';
	}

    private static function data() {
        static $data;

        if (isset($data))
            return $data;

        $data = new Container();

        $data->set('ext_script_js', array());
        $data->set('script_js', array());
        $data->set('script_css', array());
        $data->set('script_css_ie', array());

        return $data;
    }   

}

class LM extends LayoutManager {
    public function __construct() { error_log("LayoutManager cannot be called as an instance"); exit; }
}
