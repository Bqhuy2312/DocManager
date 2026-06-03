<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class DocumentTag extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'document_tags';

    protected $fillable = [
        'document_id',
        'tag_name'
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
