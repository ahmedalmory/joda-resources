<?php

namespace Ahmedjoda\JodaResources;

use Illuminate\Http\Response;
use LogicException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;
use Illuminate\Support\Facades\Validator;

trait JodaApiResource
{
    final public function __construct()
    {
        $this->setModelName();
        $this->initAttributeNames();
    }


    public function index()
    {
        if (method_exists($this, 'query')) {
            ${$this->pluralName} = $this->query($this->model::query());
        } else {
            ${$this->pluralName} = $this->model::all();
        }

        if (isset($this->resource)) {
            return $this->jsonForm($this->resource::collection(${$this->pluralName}));
        } else {
            return $this->jsonForm(${$this->pluralName});
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

        return $this->jsonForm($createdModel);
    }


    public function show($id)
    {
        ${$this->name} = $this->model::find($id);

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
            return $this->jsonForm('not found', 404, false);
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
            return $this->jsonForm('not found', 404, false);
        }

        $returned = $this->afterUpdate($updatedModel);
        if ($returned) {
            return $returned;
        }

        return $this->jsonForm($updatedModel);
    }


    public function destroy($id)
    {
        $model  = $this->model::find($id);

        $returned = $this->beforeDestroy($model);
        if ($returned) {
            return $returned;
        }

        $this->deleteFilesIfExist($model);
        $model->delete();

        $returned = $this->afterDestroy();
        if ($returned) {
            return $returned;
        }

        return 204;
    }


    public function initAttributeNames()
    {
        if (!isset($this->model)) {
            throw new LogicException('JodaApiResource can\'t find a suitable model for ' . get_class($this) .  ' please set it manually throw $model');
        }

        $array = explode('\\', $this->model);
        $name = lcfirst(end($array));
        $this->kebabName = Str::kebab(end($array));

        if (!isset($this->name)) {
            $this->name = $name;
            $this->pluralName = Str::plural($this->name);
            $this->pluralKebabName = Str::kebab(Str::plural($this->name));
            $this->pluralSnakeName = Str::snake(Str::plural($this->name));
        }
    }


    public function setModelName()
    {
        $reflector = new ReflectionClass($this);
        $model = $reflector->name;
        $array = explode('\\', $model);
        $model = str_replace('Controller', '', end($array));

        if (class_exists('App\\Models\\' . $model)) {
            $this->model = 'App\\Models\\' . $model;
        } elseif (class_exists('App\\' . $model)) {
            $this->model = 'App\\' . $model;
        } elseif (class_exists('App\\Model\\' . $model)) {
            $this->model = 'App\\Model\\' . $model;
        }
    }


    public function validateStoreRequest()
    {
        $rules = isset($this->storeRules)
            ? $this->storeRules
            : (isset($this->rules)
                ? $this->rules
                : (isset($this->model::$storeRules)
                    ? $this->model::$storeRules
                    : (
                        (isset($this->model::$rules)
                            ? $this->model::$rules
                            : null))));
        if ($rules) {
            $validator = Validator::make(request()->all(), $rules);
            if ($validator->fails()) {
                return $this->jsonForm($validator->errors(), 400, false);
            } else {
                return $validator->validated();
            }
        } else {
            throw new LogicException('there are no rules in ' . get_class($this) .  ' for store validation please set $storeRules property or $rules for both store and update in either the controller or the model');
        }
    }


    public function validateUpdateRequest()
    {
        $rules = isset($this->updateRules)
            ? $this->updateRules
            : (isset($this->rules)
                ? $this->rules
                : (isset($this->model::$updateRules)
                    ? $this->model::$updateRules
                    : (
                        (isset($this->model::$rules)
                            ? $this->model::$rules
                            : null))));
        if ($rules) {
            $validator = Validator::make(request()->all(), $rules);
            if ($validator->fails()) {
                return $this->jsonForm($validator->errors(), 400, false);
            } else {
                return $validator->validated();
            }
        } else {
            throw new LogicException('there are no rules in ' . get_class($this) .  ' for update validation please set $storeRules property or $rules for both store and update in either the controller or the model');
        }
    }


    public function uploadFilesIfExist($data)
    {
        if (isset($this->files)) {
            foreach ($this->files as $file) {
                if (request()->hasFile($file) and request()->$file) {
                    $fileName =
                        (auth()->user() ? auth()->user()->id : '') . '-' .
                        time() . '.' .
                        request()->file($file)->getClientOriginalExtension();
                    $filePath = "$this->pluralName/$fileName";
                    $data[$file] = $filePath;
                    Storage::disk('local')->put($filePath, file_get_contents(request()->$file));
                }
            }
        }
        return $data;
    }


    public function deleteFilesIfExist($model)
    {
        if (isset($this->files)) {
            foreach ($this->files as $file) {
                Storage::delete($model->$file);
            }
        }
    }


    public function beforeStore()
    {
    }

    public function afterStore($model = null)
    {
    }

    public function beforeUpdate($model = null)
    {
    }

    public function afterUpdate($model = null)
    {
    }

    public function beforeDestroy($model = null)
    {
    }

    public function afterDestroy()
    {
    }


    public static function jsonForm($data, $code = 200, $status = true)
    {
        return response(['data' => $data, 'code' => $code, 'status' => $status]);
    }
}
