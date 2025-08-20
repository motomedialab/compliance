<?php

namespace Motomedialab\Compliance\Repositories;

use Exception;
use Illuminate\Support\LazyCollection;
use Motomedialab\Compliance\Contracts\HasCompliance;

class ComplianceModelsRepository
{
    /**
     * @throws Exception
     */
    public function getModelsByClassName(
        string $className,
        ?bool $whereDoesntHaveCheck = null,
        ?bool $whereHasCheck = null
    ): LazyCollection {
        $model = $this->getModel($className);

        if ($whereDoesntHaveCheck && $whereHasCheck) {
            throw new Exception('Cannot use both whereDoesntHaveCheck and whereHasCheck');
        }

        return $model
            ->complianceQueryBuilder($model->newQuery())
            ->with('complianceCheckRecord')
            ->when($whereDoesntHaveCheck, fn ($query) => $query->doesntHave('complianceCheckRecord'))
            ->when($whereHasCheck, fn ($query) => $query->has('complianceCheckRecord'))
            ->lazy();
    }

    /**
     * @throws Exception
     */
    private function getModel(string $className): HasCompliance
    {
        if (!class_exists($className)) {
            throw new Exception($className . ' does not exist');
        }

        $model = (new $className());

        if (!$model instanceof HasCompliance) {
            throw new Exception($className . ' must implement HasComplianceRules');
        }

        return $model;
    }
}
