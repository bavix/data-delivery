<?php

namespace App\Http\Controllers;

use App\Corundum\Kit\Path;
use App\Enums\Image\ImageStatusEnum;
use App\Http\Requests\ImageRequest;
use App\Http\Resources\ImageResource;
use App\Models\Bucket;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageController extends Controller
{

    /**
     * @param Request $request
     *
     * @return AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $relations = [Image::REL_BUCKET, Image::REL_EAV];
        $image = Image::whereUserId(1)
            ->where('status', '!=', ImageStatusEnum::DELETED)
            ->orderByDesc('id')
            ->with($relations);

        return ImageResource::collection($image->paginate());
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     *
     * @return ImageResource
     */
    public function show(int $id): ImageResource
    {
        $image = Image::whereUserId(Auth::id())
            ->findOrFail($id);

        return new ImageResource($image);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  ImageRequest $request
     * @param  int $bucketId
     *
     * @return AnonymousResourceCollection
     */
    public function store(ImageRequest $request, int $bucketId): AnonymousResourceCollection
    {
        /**
         * @var UploadedFile $file
         * @var Bucket $bucket
         */
        $files = $request->files->get('file');
        $bucket = Bucket::findOrFail($bucketId);

        $collection = new Collection();
        foreach ($files as $file) {
            $model = new Image();
            $model->user_id = 1;
            $model->bucket_id = $bucketId;
            $path = Path::physical($model);
            if ($file->move(\dirname($path), \basename($path))) {
                $model->save();
                $collection->push($model->setRelation(Image::REL_BUCKET, $bucket));
            }
        }

        $paginate = new LengthAwarePaginator(
            $collection,
            $collection->count(),
            (int)\ini_get('max_file_uploads'),
            1,
            [
                'path' => Paginator::resolveCurrentPath()
            ]
        );

        return ImageResource::collection($paginate);
    }

}
