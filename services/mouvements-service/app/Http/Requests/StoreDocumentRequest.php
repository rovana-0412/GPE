<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cible_type' => ['required', 'in:bien,acquisition,affectation,transfert,maintenance'],
            'cible_id' => ['required', 'integer', 'min:1'],
            'type_document' => [
                'required',
                'in:facture_acquisition,bon_commande,proces_verbal_reception,fiche_affectation,document_transfert,document_maintenance,certificat_propriete,autre',
            ],
            'titre' => ['required', 'string', 'max:180'],
            'reference' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'date_document' => ['nullable', 'date'],
            'fichier' => [
                'required',
                'file',
                'max:10240',
                'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
                'mimetypes:application/pdf,image/jpeg,image/png,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'cible_type' => 'type de rattachement',
            'cible_id' => 'référence du rattachement',
            'type_document' => 'type de document',
            'titre' => 'titre',
            'reference' => 'référence',
            'date_document' => 'date du document',
            'fichier' => 'fichier justificatif',
        ];
    }
}
