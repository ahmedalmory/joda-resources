<?php

namespace AhmedAlmory\JodaResources;

use LogicException;

trait JodaResource
{
    use ResourceHelpers;

    public function index()
    {
        ${$this->pluralCamelName} = $this->getQuery();

        $index = ${$this->pluralCamelName};
        $route = $this->route;
        $title = trans(ucfirst($this->pluralCamelName));
        return view("{$this->view}.index", compact($this->pluralCamelName, 'index', 'route', 'title'));
    }


    public function create()
    {
        $route = $this->route;
        $title = trans('Create ' . ucfirst($this->singularCamelName));
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

        return $this->stored();
    }


    public function show($id)
    {
        ${$this->singularCamelName} = $this->model::find($id);
        $show = $this->model::find($id);
        $title = trans('Show ' . ucfirst($this->singularCamelName));
        return view("$this->view.show", compact($this->singularCamelName, 'show', 'title'));
    }


    public function edit($id)
    {
        ${$this->singularCamelName} = $this->model::find($id);
        $edit = $this->model::find($id);
        $route = $this->route;
        $title = trans('Edit ' . ucfirst($this->singularCamelName));
        return view("$this->view.edit", compact($this->singularCamelName, 'edit', 'route', 'title'));
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
        $model->delete();

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
            return request()->validate($rules);
        } else {
            throw new LogicException('there are no rules in ' . get_class($this) .  ' for store validation please set $storeRules property or $rules for both store and update in either the controller or the model');
        }
    }

    public function validateUpdateRequest()
    {
        $rules = $this->updateRules();
        if ($rules) {
            return request()->validate($rules);
        } else {
            throw new LogicException('there are no rules in ' . get_class($this) .  ' for update validation please set $storeRules property or $rules for both store and update in either the controller or the model');
        }
    }

    protected function stored()
    {
        return redirect(route("$this->route.index"))->with('success', trans('joda-resources::app.added'));
    }

    protected function updated()
    {
        return redirect(route("$this->route.index"))->with('success', trans('joda-resources::app.updated'));
    }

    protected function destroyed(){
        return redirect(route("$this->route.index"))->with('success', trans('joda-resources::app.deleted'));
    }
}
