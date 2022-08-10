<?php

namespace AhmedAlmory\JodaResources;

use Illuminate\Http\Response;
use LogicException;
use Illuminate\Support\Facades\Validator;

trait JodaApiResource
{
    use ResourceHelpers;
    public function index()
    {
        ${$this->pluralCamelName} = $this->getQuery();

        if (isset($this->resource)) {
            return $this->jsonForm($this->resource::collection(${$this->pluralCamelName}));
        } else {
            return $this->jsonForm(${$this->pluralCamelName});
        }
    }


    public function store()
    {
        $returned = $this->beforeStore();
        if ($returned) {
            return $returned;
        }

        $data = $this->validateStoreRequest();
        if ($data instanceof Response) {
            return $data;
        }

        $data = $this->uploadFilesIfExist($data);
        $createdModel = $this->model::create($data);

        $returned = $this->afterStore($createdModel);
        if ($returned) {
            return $returned;
        }

        return $this->stored();
    }


    public function show($id)
    {
        ${$this->singularCamelName} = $this->model::find($id);

        if (method_exists($this, 'query')) {
            $show = $this->query($this->model::query())->find($id);
        } else {
            $show = $this->model::find($id);
        }

        if ($show) {
            if (isset($this->resource)) {
                return  $this->jsonForm(new $this->resource($show));
            }
            return $this->jsonForm($show);
        } else {
            return $this->jsonForm(Response::$statusTexts[Response::HTTP_NOT_FOUND], Response::HTTP_NOT_FOUND);
        }
    }


    public function update($id)
    {
        $model = $this->model::find($id);
        $returned = $this->beforeUpdate($model);
        if ($returned) {
            return $returned;
        }

        $data = $this->validateUpdateRequest();
        if ($data instanceof Response) {
            return $data;
        }

        $data = $this->uploadFilesIfExist($data);

        if ($model) {
            $updatedModel = tap($model)->update($data);
        } else {
            return $this->jsonForm(Response::$statusTexts[Response::HTTP_NOT_FOUND], Response::HTTP_NOT_FOUND);
        }

        $returned = $this->afterUpdate($updatedModel);
        if ($returned) {
            return $returned;
        }

        return $this->updated();
    }


    public function destroy($id)
    {
        $model  = $this->model::find($id);

        $returned = $this->beforeDestroy($model);
        if ($returned) {
            return $returned;
        }

        $this->deleteFilesIfExist($model);
        if ($model) {
            $model->delete();
        } else {
            return $this->jsonForm(Response::$statusTexts[Response::HTTP_NOT_FOUND], Response::HTTP_NOT_FOUND);
        }

        $returned = $this->afterDestroy();
        if ($returned) {
            return $returned;
        }

        return $this->destroyed();
    }


    public function validateStoreRequest()
    {
        $rules = $this->storeRules();
        if ($rules) {
            $validator = Validator::make(request()->all(), $rules);
            if ($validator->fails()) {
                return $this->jsonForm($validator->errors(), Response::HTTP_BAD_REQUEST);
            } else {
                return $validator->validated();
            }
        } else {
            throw new LogicException('there are no rules in ' . get_class($this) .  ' for store validation please set $storeRules property or $rules for both store and update in either the controller or the model');
        }
    }


    public function validateUpdateRequest()
    {
        $rules = $this->updateRules();
        if ($rules) {
            $validator = Validator::make(request()->all(), $rules);
            if ($validator->fails()) {
                return $this->jsonForm($validator->errors(), Response::HTTP_BAD_REQUEST);
            } else {
                return $validator->validated();
            }
        } else {
            throw new LogicException('there are no rules in ' . get_class($this) .  ' for update validation please set $storeRules property or $rules for both store and update in either the controller or the model');
        }
    }

    public static function jsonForm($data, $code = 200)
    {
        if ($code >= 400) {
            return response(['error message' => $data,"code"=>$code,"status"=>false], $code);
        }
        return response(['data' => $data,"code"=>$code,"status"=>true], $code);
    }

    protected function stored()
    {
        return $this->jsonForm($createdModel, Response::HTTP_CREATED);
    }

    protected function updated()
    {
        return $this->jsonForm($updatedModel);
    }
    
    protected function destroyed()
    {
        return $this->jsonForm(null, Response::HTTP_NO_CONTENT);
    }
}
