<?php

namespace App\Http\Requests;

use App\Models\Service as ServiceModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';
        $service = $this->route('service');
        $serviceModel = $service instanceof ServiceModel ? $service : ServiceModel::find($service);
        $ministereId = $this->input('ministere_id', $serviceModel?->ministere_id);
        $serviceId = $serviceModel?->getKey() ?? $service;

        return [
            'ministere_id' => [$required, 'integer', 'exists:ministeres,id'],
            'nom' => [
                $required,
                'string',
                'max:150',
                Rule::unique('services', 'nom')
                    ->where(fn ($query) => $query->where('ministere_id', $ministereId))
                    ->ignore($serviceId),
            ],
            'sigle' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'actif' => ['sometimes', 'boolean'],
        ];
    }
}
