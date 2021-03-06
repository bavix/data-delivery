<?php

namespace App\Http\Requests;

class ImageRequest extends FileRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return \array_merge(parent::rules(), [
            'file' => ['required', 'image'],
        ]);
    }

}
