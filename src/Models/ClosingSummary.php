<?php

namespace ME\SflInventory\Models;

use App\Models\User;
use App\Traits\HasAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/** Style Closing Summary — typed by hand, no number, no approval. */
class ClosingSummary extends Model
{
    use HasAudit;
    use SoftDeletes;

    /** Folder on the "public" disk the uploaded logos go to. */
    public const LOGO_DIR = 'inventory/closing-summary-logos';

    protected $table = 'inv_closing_summaries';

    protected $fillable = ['report_date', 'factory_name', 'factory_location', 'po_no', 'logo', 'comments', 'created_by'];

    protected $casts = ['report_date' => 'date'];

    /**
     * The logo as a data: URI, so it shows on screen and paper whether or not
     * the server has the public/storage link. Null when there is none.
     */
    public static function logoSrc(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }
        $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode(Storage::disk('public')->get($path));
    }

    /** Logos uploaded on earlier summaries, newest first — to pick again without re-uploading. */
    public static function earlierLogos(): array
    {
        return static::withTrashed()->whereNotNull('logo')->latest('id')->pluck('logo')->unique()
            ->filter(fn ($p) => Storage::disk('public')->exists($p))->values()->all();
    }

    public function products(): HasMany
    {
        return $this->hasMany(ClosingSummaryProduct::class)->orderBy('sequence');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
