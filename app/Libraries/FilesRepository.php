<?php namespace App\Libraries;

use App\Models\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Config;
use Intervention\Image\Facades\Image as Image;

class FilesRepository
{
    public function upload($request)
    {
        $error = false;
        $code = 200;
        $thumbnail_id;
        $file_id;

        $file = new File();
        
        $photo = $request['file'];
        $originalName = $photo->getClientOriginalName();
        $filename = pathinfo($originalName)['filename'];
        $extension = pathinfo($originalName)['extension'];
        $sanitized = $this->sanitize($filename);
        $file->name = $sanitized.".".$extension;

        switch ($request['target']) {
            case "tickets" : $path = "attachments"; break;
            case "posts" : $path = "attachments"; break;
            case "people" : $path = "profiles"; break;
            case "companies" : $path = "profiles"; break;
        }

        $file->file_path = $path;

        $file->file_name = $this->createUniqueFilename($request['target'],$request['target_id'],$request['uploader_id'],$extension);

        $file->file_extension = $extension;

        $model = ucfirst(str_singular($request['target']));
        $model = "App\\Models\\".$model;
        $file->resource_type = $model;

        $file->resource_id = $request['target_id'];
        $file->uploader_id = $request['uploader_id'];

        $copy_result = $request['file']->move(RESOURCES.DS.$file->file_path,$file->file_name);
        
        // crops the image
        if ($request['target'] == "people") {
            $this->cropPerson(RESOURCES.DS.$file->file_path.DS.$file->file_name,100,100);
        }
        elseif ($request['target'] == "companies") {
            $this->cropCompany(RESOURCES.DS.$file->file_path.DS.$file->file_name,280);
        }

        if ($copy_result) {
                
            $thumbnail = $this->createThumbnail($file);

            if (!$thumbnail) {
                $thumbnail_id = null;
                $message = "Thumbnail can't be copied";
            }
            else {
                $thumbnail_id = $thumbnail->id;
            }

            $result = $file->save();

            if ($result) {
                $file_id = $file->id;
                $message = "File copied!";
            }
            else {
                unlink(ATTACHMENTS.DS.$file->file_name);
                
                if ($thumbnail) {
                    unlink($thumbnail->real_path());   
                }

                $message = "File can't insert into db";
                $error = true;
                $code = 500;
            }
        }
        else {
            $message = "File can't be copied";
            $error = true;
            $code = 500;
        }

        $response = [
            'id' => $file_id,
            'error' => $error,
            'message' => $message,
            'thumbnail_id' => $thumbnail_id,
            'code'  => $code
        ];

        return $response;
    }

    public function destroy($id) {
        
        $remove_file = $this->removeFile($id);
        $remove_thumb = File::find($id) ? $this->removeFile(File::find($id)->thumbnail_id) : true;
        
        $error = !$remove_thumb || !$remove_file;
        
        $response = Response::json([
            'error' => $error,
            'code' => $error ? 500 : 200
        ]);
        return $response;
    }

    private function removeFile($id) {
        
        $success = false;
        $file = File::find($id);
        
        if (unlink($file->real_path())) {
            $success = File::find($id)->forceDelete();
        }
        return $success;
    }

    private function sanitize($string, $force_lowercase = true, $anal = false)
    {
        $strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "=", "+", "[", "{", "]",
            "}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
            "â€”", "â€“", ",", "<", ".", ">", "/", "?");
        $clean = trim(str_replace($strip, "", strip_tags($string)));
        $clean = preg_replace('/\s+/', "-", $clean);
        $clean = ($anal) ? preg_replace("/[^a-zA-Z0-9]/", "", $clean) : $clean ;

        return ($force_lowercase) ?
            (function_exists('mb_strtolower')) ?
                mb_strtolower($clean, 'UTF-8') :
                strtolower($clean) :
            $clean;
    }

    private function createUniqueFilename($target,$target_id,$uploader_id,$extension)
    {
        return strtoupper(str_singular($target))."#".$target_id."UPLOADER#".$uploader_id."UUID#".uniqid().".".$extension;
    }

    private function cropPerson($source_url, $width, $height) {
        $img = Image::make($source_url)->fit($width, $height)->save();
    }

    private function cropCompany($source_url, $width) {
        $img = Image::make($source_url)->resize($width, null, function ($constraint) {
            $constraint->aspectRatio();
        })->resizeCanvas($width, $width, 'center', false, 'ffffff')->save();
    }

    private function createThumbnail($file) {

        $response = false;
        $remove_from_temp = false;

        $path_info = pathinfo($file['file_name']);

        if (!in_array($path_info['extension'],['zip','7z','rar','pam','tgz','bz2','iso','ace'])) 
        {
            $path = RESOURCES.DS.$file['file_path'].DS.$file['file_name'];
        
            if (in_array($path_info['extension'],['xlsx','xls','docx','doc','odt','ppt','pptx','pps','ppsx','txt','csv','log'])) 
            {
                $command = env('LIBREOFFICE','soffice')." --headless --convert-to pdf:writer_pdf_Export --outdir ".TEMP." ".$path." > /dev/null";
                exec($command);
                $source = TEMP.DS.$path_info['filename'].".pdf[0]";
                $remove_from_temp = TEMP.DS.$path_info['filename'].".pdf";
            } 
            elseif (in_array($path_info['extension'],['mp4','mpg','avi','mkv','flv','xvid','divx','mpeg','mov','vid','vob'])) {
                $command = env('FFMPEG','ffmpeg')." -i ".$path." -ss 00:00:01.000 -vframes 1 ".TEMP.DS.$path_info['filename'].".png > /dev/null";
                exec($command);
                $source = TEMP.DS.$path_info['filename'].".png";
                $remove_from_temp = $source;
            } 
            else {
                $path .= $path_info["extension"] == "pdf" ? "[0]" : ""; 
                $source = $path;
            }

            $destination = THUMBNAILS.DS.$path_info['filename'].".png";
            $command2 = env('CONVERT','convert')." -resize '384x384' $source $destination";
            
            $result = exec($command2);

            if (file_exists($destination)) {

                if (file_exists($destination)) {
                    if ($remove_from_temp) unlink($remove_from_temp);
                }

                $thumbnail = new File();
                $thumbnail->name = $file['name'];
                $thumbnail->file_path = 'thumbnails';
                $thumbnail->file_name = pathinfo($file['file_name'])['filename'].".png";
                $thumbnail->file_extension = 'png';
                $thumbnail->resource_type = "Thumbnail";
                $thumbnail->uploader_id = $file['uploader_id'];

                $created = $thumbnail->save();

                if ($created) {
                    $file->thumbnail_id = $thumbnail->id;
                    $updated = $file->save();
                    if ($updated) {
                        $response = $thumbnail;
                    }
                }
            }
        }

        return $response;
    }
}
