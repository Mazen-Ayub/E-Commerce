<?php


namespace Marvel\Http\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Marvel\Database\Models\Category;
use Marvel\Database\Repositories\CategoryRepository;
use Marvel\Exceptions\MarvelException;
use Marvel\Http\Requests\CategoryCreateRequest;
use Marvel\Http\Requests\CategoryUpdateRequest;
use Marvel\Http\Resources\CategoryResource;
use Prettus\Validator\Exceptions\ValidatorException;


class CategoryController extends CoreController
{
    public $repository;

    public function __construct(CategoryRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Collection|Category[]
     */
    public function index(Request $request)
    {
        $language = $request->language ?? DEFAULT_LANGUAGE;
        $parent = $request->parent;
        $selfId = $request->self;
        $limit = $request->limit ?? 15;

        $categoriesQuery = $this->repository->with(['type', 'parent', 'children'])
            ->where('language', $language);

        if ($parent === 'null') {
            $categoriesQuery->whereNull('parent');
        }
        if ($selfId) {
            $categoriesQuery->where('id', '!=', $selfId);
        }

        $categories = $categoriesQuery->paginate($limit);
        $data = CategoryResource::collection($categories)->response()->getData(true);
        return formatAPIResourcePaginate($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CategoryCreateRequest $request
     * @return mixed
     * @throws ValidatorException
     */
    public function store(CategoryCreateRequest $request)
    {
        try {
            return $this->repository->saveCategory($request);
        } catch (MarvelException $th) {
            throw new MarvelException(COULD_NOT_CREATE_THE_RESOURCE);
        }
        // $language = $request->language ?? DEFAULT_LANGUAGE;
        // $translation_item_id = $request->translation_item_id ?? null;
        // $category->storeTranslation($translation_item_id, $language);
        // return $category;
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, $params)
    {
        try {
            $language = $request->language ?? DEFAULT_LANGUAGE;
            if (is_numeric($params)) {
                $params = (int) $params;
                $category = $this->repository->with(['type', 'parentCategory', 'children'])->where('id', $params)->firstOrFail();
                return new CategoryResource($category);
            }
            $category = $this->repository->with(['type', 'parentCategory', 'children'])->where('slug', $params)->firstOrFail();
            return new CategoryResource($category);
        } catch (MarvelException $e) {
            throw new MarvelException(NOT_FOUND);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param CategoryUpdateRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(CategoryUpdateRequest $request, $id)
    {
        try {
            $request->merge(['id' => $id]);
            return $this->categoryUpdate($request);
        } catch (MarvelException $e) {
            throw new MarvelException(NOT_FOUND);
        }
    }


    public function categoryUpdate(CategoryUpdateRequest $request)
    {
        $category = $this->repository->findOrFail($request->id);
        return $this->repository->updateCategory($request, $category);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        try {
            return $this->repository->findOrFail($id)->delete();
        } catch (MarvelException $e) {
            throw new MarvelException(NOT_FOUND);
        }
    }
}
