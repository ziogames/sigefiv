<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZoeIncidencia extends Model
{
    use HasFactory;

    // Nombre exacto de la tabla en tu base de datos
    protected $table = 'zoe_incidencias';

    // Campos permitidos para inserción masiva
    protected $fillable = [
        'user_id',
        'mensaje',
        'imagen_path',
        'estado',
    ];

    // Relación opcional con el usuario/vecino que reporta (si tu tabla users tiene este modelo)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}