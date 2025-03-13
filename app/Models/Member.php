<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property string $id
 * @property string $first_name
 * @property string $last_name
 * @property string $birth_place
 * @property string|null $birth_date
 * @property string $phone_number
 * @property string $address
 * @property string|null $personal_id_number
 * @property string $picture
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member whereBirthDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member whereBirthPlace($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member wherePersonalIdNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member wherePicture($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member whereUpdatedAt($value)
 * @property-read mixed $qr_code
 * @mixin \Eloquent
 */
class Member extends Model
{
    use HasUuids;

    protected $appends = ['qr_code'];

    protected $fillable = [
        'first_name',
        'last_name',
        'birth_place',
        'birth_date',
        'address',
        'phone_number',
        'personal_id_number',
        'picture',
    ];

    public function getPersonalIdNumberAttribute($value)
    {
        return $value ?? '';
    }

    public function getPictureAttribute($value)
    {
        return $value ?? '';
    }

    protected function qrCode(): Attribute
    {
        return Attribute::make(
            get: fn() => asset('/storage/' . $this->getQRCodeFileName()),
        );
    }

    public function getQRCodeFileName()
    {
        $qrCodeFileName = 'qr-' . $this->id . '.svg';

        return $qrCodeFileName;
    }
}
