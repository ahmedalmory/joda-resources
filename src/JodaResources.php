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
        return view("{$this->view}.index", compact($this->pluralName, 'index', 'route'));
    }


    public function create()
    {
        $route = $this->route;
        $title = trans('create');
        return view("{$this->view}.create", compact('route', 'title'));
    }


    public function store()
    {
        $this->validateStoreRequest();

        $returned = $this->beforeStore();
        if ($returned) {
            return $returned;
        }

        $data = $this->uploadFilesIfExist();
        $data = $this->removeExcludedItems($data);
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
        $title = trans('show');
        return view("$this->view.show", compact($this->name, 'show', 'title'));
    }


    public function edit($id)
    {
        ${$this->name} = $this->model::find($id);
        $edit = $this->model::find($id);
        $route = $this->route;
        $title = trans('edit');
        return view("$this->view.edit", compact($this->name, 'edit', 'route', 'title'));
    }


    public function update($id)
    {
        $this->validateUpdateRequest();

        $model = $this->model::find($id);
        $returned = $this->beforeUpdate($model);
        if ($returned) {
            return $returned;
        }

        $data = $this->uploadFilesIfExist();
        $data = $this->removeExcludedItems($data);
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
            throw new LogicException(get_class($this) . ' is using JodaResources it must have at least a $model value');
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
        $namespace = Str::lower(str_replace('App\Http\Controllers\\', '', $reflector->getNamespaceName()));

        if (!isset($this->view)) {
            $this->view = "$namespace.$this->kebabName";
        }

        if (!isset($this->route)) {
            $this->route = "$namespace.$this->pluralKebabName";
        }
    }


    public function validateStoreRequest()
    {
        $rules = isset($this->model::$storeRules) ? $this->model::$storeRules : (isset($this->model::$rules) ? $this->model::$rules : null);
        if ($rules) {
            request()->validate($rules);
        }
    }

    public function validateUpdateRequest()
    {
        $rules = isset($this->model::$updateRules) ? $this->model::$updateRules : (isset($this->model::$rules) ? $this->model::$rules : null);
        if ($rules) {
            request()->validate($rules);
        }
    }

    public function uploadFilesIfExist()
    {
        $data = request()->except("_token", '_method');
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

    public function removeExcludedItems($data)
    {
        if (isset($this->exclude)) {
            foreach ($this->exclude as $excluded) {
                unset($data[$excluded]);
            }
        }
        return $data;
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
