<?php

class WaterMark{
     private $im_src;          // 背景图文件
     private $im_src_width;    // 背景图宽度
     private $im_src_height;   // 背景图高度
     private $src_im;          // 由背景图创建的新图
     private $im_water;        // 水印图文件
     private $im_water_width;  // 水印图宽度
     private $im_water_height; // 水印图高度
     private $water_im;        // 由水印图创建的新图
     private $font;            // 字体库
     private $font_text;       // 文本
     private $font_size;       // 字体大小
     private $font_color;      // 字体颜色
     private $type;            //类型
     private $mime;
             
    function setImSrc($img,$width = 0){
        $this->im_src = $img;
        //读取背景图片文件
        $srcInfo = @getimagesize($this->im_src);
        $this->im_src_width = $srcInfo[0];
        $this->im_src_height = $srcInfo[1];
        $this->type = $srcInfo[2];
        $this->mime = $srcInfo['mime'];
        //取得背景图片
        $this->src_im = $this->getType($this->im_src, $srcInfo[2]);
        if($width){
          $this->resize($width);
        }
     }

     function setImWater($img){
        $this->im_water = $img;
        //读取水印图片文件
        $waterInfo = @getimagesize($this->im_water);
        $this->im_water_width = $waterInfo[0];
        $this->im_water_height = $waterInfo[1];
        //取得水印图片
        $this->water_im = $this->getType($this->im_water, $waterInfo[2]);
     }

     function setFont($font, $text, $size, $color){
          $this->font = $font;
          $this->font_text = $text;
          $this->font_size = $size;
          //水印文字颜色（'255,255,255'）
          $this->font_color = $color;
     }

     /**
      * 根据文件或URL创建一个新图象
      * @param $img
      * @param $type
      * @return resource
      */
     function getType($img, $type){
        switch($type){
            case 1:
                $im = imagecreatefromgif($img);                
                break;
            case 2:
                $exif = exif_read_data($img, 'EXIF');
                $im = imagecreatefromjpeg($img);
                if($exif!=false){
                    switch($exif['Orientation']) {
                        case 8:
                            $im = imagerotate($im,90,0);
                            break;
                        case 3:
                            $im = imagerotate($im,180,0);
                            break;
                        case 6:
                            $im = imagerotate($im,-90,0);
                            break;
                    }
                }
                break;
            case 3:
                $im = imagecreatefrompng($img);
                imagesavealpha($im, true);
                break;
            default:break;
        }
      return $im;
     }

     /**
      * 根据位置及水印宽高，获取打印的x/y坐标
      * @param $pos
      * @param $w
      * @param $h
      */
     function getPos($pos, $w, $h)
     {
      switch($pos){
       case 0://随机
        $posX = rand(0, ($this->im_src_width - $w));
        $posY = rand(0, ($this->im_src_height - $h));
        break;
       case 1://1为顶端居左
        $posX = 0;
        $posY = 0;
        break;
       case 2://2为顶端居中
        $posX = ceil($this->im_src_width - $w) / 2;
        $posY = 0;
        break;
       case 3://3为顶端居右
        $posX = $this->im_src_width - $w;
        $posY = 0;
        break;
       case 4://4为中部居左
        $posX = 0;
        $posY = ceil($this->im_src_height - $h) / 2;
        break;
       case 5://5为中部居中
        $posX = ceil($this->im_src_width - $w) / 2;
        $posY = ceil($this->im_src_height - $h) / 2;
        break;
       case 6://6为中部居右
        $posX = $this->im_src_width - $w;
        $posY = ceil($this->im_src_height - $h) / 2;
        break;
       case 7://7为底端居左
        $posX = 0;
        $posY = $this->im_src_height - $h;
        break;
       case 8://8为底端居中
        $posX = ceil($this->im_src_width - $w) / 2;
        $posY = $this->im_src_height - $h;
        break;
       case 9://9为底端居右
        $posX = $this->im_src_width - $w;
        $posY = $this->im_src_height - $h;
        break;
       default://随机
        $posX = rand(0,($this->im_src_width - $w));
        $posY = rand(0,($this->im_src_height - $h));
        break;
      }
      return array($posX, $posY);
     }

   /**
    * 校验尺寸
    * @param $w
    * @param $h
    * @return boolean
    */
    function check_range($w, $h){
        if( ($this->im_src_width < $w) || ($this->im_src_height < $h) ){
            return false;
        }
        return true;
    }

     /**
      * 打水印操作
      * @param $is_image   是1否0水印图片
      * @param $image_pos  水印图片位置（0~9）
      * @param $is_text    是1否0水印文字
      * @param $text_pos   水印文字位置（0~9）
      */
    function mark($is_image=0, $image_pos=0, $is_text=0, $text_pos=0, $mic_x=0, $mic_y=0, $alpha=0, $save=FALSE){
        // 水印图片情况
        if ($is_image)
        { 
            // 校验图片大小，太小无法加水印直接返回
            if (!$this->check_range($this->im_water_width, $this->im_water_height)){
                $this->show();
                $this->clean();
                return;
            }
            $posArr = $this->getPos($image_pos, $this->im_water_width, $this->im_water_height);
            $posX = $posArr[0];
            $posY = $posArr[1];
            // 拷贝水印到目标文件
            if($alpha){
                $this->imagecopymerge_alpha($this->src_im, $this->water_im, $posX, $posY, 0, 0, $this->im_water_width, $this->im_water_height, $alpha);
            }else{
                imagecopy($this->src_im, $this->water_im, $posX, $posY, 0, 0, $this->im_water_width, $this->im_water_height);
            }
        }
        // 水印文字情况
        if ($is_text){          
            //取得此字体的文本的范围
            $temp = imagettfbbox($this->font_size, 0, $this->font, $this->font_text);
            $w = $temp[2] - $temp[0];
            $h = $temp[1] - $temp[7];
            unset($temp);
            // 校验图片大小，太小无法加水印直接返回
            if (!$this->check_range($w, $h)){
                $this->show();
                $this->clean();
                return;
            }

            $posArr = $this->getPos($text_pos, $w, $h);
            $posX = $posArr[0]+$mic_x;
            $posY = $posArr[1]+$mic_y;
            // 打印文本 
            $rgb = explode(',', $this->font_color);
            if (count($rgb) != 3){
                $rgb = $this->hex2rgb($this->font_color);
                file_put_contents('log.txt',$this->font_color);
                $color = imagecolorallocate($this->src_im, $rgb['r'], $rgb['g'], $rgb['b']);
            }else{
                $color = imagecolorallocate($this->src_im, $rgb[0], $rgb[1], $rgb[2]);
            }
            imagettftext($this->src_im, $this->font_size, 0, $posX, $posY, $color, $this->font, $this->font_text);
        }
        // 输出
        $this->show();
        if($save){
        $this->save($save);            
        }
        // 清理
        $this->clean();
    }

     /**
      * 输出图像
      */
     function show() {
         ob_clean();
         header("Content-type: {$this->mime}; charset=UTF-8");
         switch ($this->type){
             case 1:
                 imagegif($this->src_im);
                 break;
             case 2:
                 imagejpeg($this->src_im);
                 break;
             default :
                 imagepng($this->src_im);
         }         
     }
      /**
      * 存储图像
      */
    function save($file){
        switch ($this->type){
             case 1:
                 imagegif($this->src_im,$file);
                 break;
             case 2:
                 imagejpeg($this->src_im,$file);
                 break;
             default :
                 imagepng($this->src_im,$file);
         }     
    }
     /**
      * 清理
      */
     function clean(){
          imagedestroy($this->water_im);
          imagedestroy($this->src_im);
     } 
     
    /**
     * 将十六进制颜色转为RGB
     * @param type $hexColor
     * @return array
     */
    function hex2rgb($hexColor) {
        $color = substr($hexColor,1);
        if (strlen($color) > 3) {
            $rgb = array(
                'r' => hexdec(substr($color, 0, 2)),
                'g' => hexdec(substr($color, 2, 2)),
                'b' => hexdec(substr($color, 4, 2))
            );
        } else {
            $color = $hexColor;
            $r = substr($color, 0, 1) . substr($color, 0, 1);
            $g = substr($color, 1, 1) . substr($color, 1, 1);
            $b = substr($color, 2, 1) . substr($color, 2, 1);
            $rgb = array(
                'r' => hexdec($r),
                'g' => hexdec($g),
                'b' => hexdec($b)
            );
        }
        return $rgb;
    }
    
     function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct){
        $opacity=$pct;
        // getting the watermark width
        $w = imagesx($src_im);
        // getting the watermark height
        $h = imagesy($src_im);         
        // creating a cut resource
        $cut = imagecreatetruecolor($src_w, $src_h);
        // copying that section of the background to the cut
        imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
        // inverting the opacity
        $opacity = 100 - $opacity;         
        // placing the watermark now
        imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);
        imagecopymerge($dst_im, $cut, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $opacity);
    }
    /**
     * 重设图片大小
     * 
     * @param type $maxwidth
     */
     function resize($maxwidth=600){
        $im = $this->src_im;
        $width = imagesx($im);
        $height = imagesy($im);
        if($maxwidth && $width > $maxwidth){                
            $ratio = $maxwidth/$width;               
            $this->im_src_width  = $width * $ratio;
            $this->im_src_height = $height * $ratio;
            if(function_exists("imagecopyresampled")){
                  $newim = imagecreatetruecolor($this->im_src_width , $this->im_src_height);
                  if(3 === $this->type){
                      imagealphablending($newim, false);
                      imagesavealpha($newim, true);
                  }
                  imagecopyresampled($newim, $im, 0, 0, 0, 0, $this->im_src_width , $this->im_src_height, $width, $height);
            }else{
                $newim = imagecreate($this->im_src_width , $this->im_src_height); 
                if(3 === $this->type){
                      imagealphablending($newim, false);
                      imagesavealpha($newim, true);
                  }
                imagecopyresized($newim, $im, 0, 0, 0, 0, $this->im_src_width , $this->im_src_height, $width, $height);
            }
             $this->src_im = $newim;
        }          
     }
}
?>
