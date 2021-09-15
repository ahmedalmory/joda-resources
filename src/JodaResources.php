<?php

namespace Ahmedjoda\JodaResources;

use LogicException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;

trait JodaResources
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
            ${$this->pluralName} = $this->model::paginate()->withQueryString();
        }

        $index = ${$this->pluralName};
        $route = $this->route;
        $title = ucfirst($this->pluralName);
        return view("{$this->view}.index", compact($this->pluralName, 'index', 'route', 'title'));
    }


    public function create()
    {
        $route = $this->route;
        $title = trans('Create ' . ucfirst($this->name));
        return view("{$this->view}.create", compact('route', 'title'));
    }


    public function store()
    {
        $returned = $this->beforeStore();
        if ($returned) {
            return $returned;
        }

        $data = $this->validateStoreRequest();

        $data = $this->uploadFilesIfExist($data);
        $createdModel = $this->model::create($data);

        $returned = $this->afterStore($createdModel);
        if ($returned) {
            return $returned;
        }

        return redirect(route("$this->route.index"))->with('success', trans('added'));
    }


    public function show($id)
    {
        ${$this->name} = $this->model::find($id);
        $show = $this->model::find($id);
        $title = trans('Show ' . ucfirst($this->name));
        return view("$this->view.show", compact($this->name, 'show', 'title'));
    }


    public function edit($id)
    {
        ${$this->name} = $this->model::find($id);
        $edit = $this->model::find($id);
        $route = $this->route;
        $title = trans('Edit ' . ucfirst($this->name));
        return view("$this->view.edit", compact($this->name, 'edit', 'route', 'title'));
    }


    public function update($id)
    {
        $model = $this->model::find($id);
        $returned = $this->beforeUpdate($model);
        if ($returned) {
            return $returned;
        }

        $data = $this->validateUpdateRequest();

        $data = $this->uploadFilesIfExist($data);
        $updatedModel = $model->update($data);

        $returned = $this->afterUpdate($updatedModel);
        if ($returned) {
            return $returned;
        }

        return redirect(route("$this->route.index"))->with('success', trans('updated'));
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

        return redirect(route("$this->route.index"))->with('success', trans('deleted'));
    }


    public function initAttributeNames()
    {
        if (!isset($this->model)) {
            throw new LogicException('JodaResources can\'t find a suitable model for ' . get_class($this) .  ' please set it manually throw $model');
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

        $reflector = new ReflectionClass($this);
        $namespace = '';
        if ($reflector->getNamespaceName() != 'App\Http\Controllers')
            $namespace = Str::lower(str_replace('App\Http\Controllers\\', '', $reflector->getNamespaceName()));

        if (!isset($this->view)) {
            $this->view =  $namespace ? "$namespace.$this->kebabName" : "$this->kebabName";
        }

        if (!isset($this->route)) {
            $this->route = $namespace ? "$namespace.$this->pluralKebabName" : "$this->pluralKebabName";
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
            return request()->validate($rules);
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
            return request()->validate($rules);
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
}
