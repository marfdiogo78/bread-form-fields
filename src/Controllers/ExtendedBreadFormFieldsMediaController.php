<?php

namespace ExtendedBreadFormFields\Controllers;

use Exception;
use Illuminate\Http\Request;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\VoyagerMediaController;

class ExtendedBreadFormFieldsMediaController extends VoyagerMediaController
{
    
    public function remove(Request $request)
    {
        if($request->get('multiple_ext')){
            try {
                // GET THE SLUG, ex. 'posts', 'pages', etc.
                $slug = $request->get('slug');
    
                // GET image name
                $image = $request->get('image');
    
                // GET record id
                $id = $request->get('id');
    
                // GET field name
                $field = $request->get('field');
    
                // GET THE DataType based on the slug
                $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();
                $dataRow = Voyager::model('DataRow')
                                    ->where('data_type_id', '=', $dataType->id)
                                    ->where('type', '=', 'multiple_images_with_attrs')
                                    ->first();

                // Check permission
                //Voyager::canOrFail('delete_'.$dataType->name);
                $this->authorize('delete', app($dataType->model_name));

                $exploded = explode(".", $image);

                if (count($dataRow->details->thumbnails) > 0) {
                    for ($i=0; $i < count($dataRow->details->thumbnails); $i++) { 
                        $nameThumbnails = $dataRow->details->thumbnails[$i]->name;

                        // Thumbnails
                        $imageThumbnail = $exploded[0].'-'.$nameThumbnails.'.'.$exploded[1];

                        // Remove image cropped storage path
                        $pathDelete = storage_path().'\app\public\\'.$imageThumbnail;
                        unlink($pathDelete);
                    }
                }
                
                $nameThumbnails = $dataRow->details->thumbnails[0]->name;

                // Image
                $image = $exploded[0].'.'.$exploded[1];

                // Remove image storage path
                $pathDelete = storage_path().'\app\public\\'.$image;
                unlink($pathDelete);
    
                // Load model and find record
                $model = app($dataType->model_name);
                $data = $model::find([$id])->first();
    
                // Check if field exists
                if (!isset($data->{$field})) {
                    throw new Exception(__('voyager::generic.field_does_not_exist'), 400);
                }
    
                // Check if valid json
                if (is_null(@json_decode($data->{$field}))) {
                    throw new Exception(__('voyager::json.invalid'), 500);
                }
                
                // Decode field value
                $fieldData = @json_decode($data->{$field}, true);
                foreach ($fieldData as $i => $single) {
                    // Check if image exists in array
                    if(in_array($image,array_values($single)))
                        $founded = $i;
                }
                if(!isset($founded))
                    throw new Exception(__('voyager::media.image_does_not_exist'), 400);
                
                // Remove image from array
                unset($fieldData[$founded]);
    
                // Generate json and update field
                $data->{$field} = json_encode($fieldData);

                $data->save();
    
                return response()->json([
                   'data' => [
                       'status'  => 200,
                       'message' => __('voyager::media.image_removed'),
                   ],
                ]);
            } catch (Exception $e) {
                $code = 500;
                $message = __('voyager::generic.internal_error');
    
                if ($e->getCode()) {
                    $code = $e->getCode();
                }
    
                if ($e->getMessage()) {
                    $message = $e->getMessage();
                }
    
                return response()->json([
                    'data' => [
                        'status'  => $code,
                        'message' => $message,
                    ],
                ], $code);
            }
        } else{
            VoyagerMediaController::remove($request);
        }
    }

}
