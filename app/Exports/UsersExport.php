<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Database\Eloquent\Builder;

class UsersExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    protected $startDate;
    protected $endDate;

    /**
     * Create a new export instance.
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return void
     */
    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return Builder
     */
    public function query()
    {
        $query = User::query()->with(['tier', 'kycs', 'bankAccounts']);

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [$this->startDate, $this->endDate]);
        }

        return $query->orderBy('created_at', 'desc');
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Username',
            'Email',
            'Phone',
            'Status',
            'Tier Level',
            'Wallet Balance',
            'Total Crypto Trade',
            'Total Withdrawn',
            'Referral Code',
            'Referral Amount Available',
            'Referral Amount Redeemed',
            'Has PIN',
            'Has BVN Verified',
            'Has Default Bank Account',
            'Has Withdrawn',
            'Email Verified At',
            'Created At',
            'Updated At'
        ];
    }

    /**
     * @param User $user
     * @return array
     */
    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
            $user->username,
            $user->email,
            $user->phone,
            $user->status,
            $user->tier ? $user->tier->name : 'N/A',
            number_format($user->wallet_balance, 2),
            number_format($user->total_crypto_trade, 2),
            number_format($user->total_withdrawn, 2),
            $user->referral_code,
            number_format($user->referral_amount_available, 2),
            number_format($user->referral_amount_redeemed, 2),
            $user->has_pin ? 'Yes' : 'No',
            $user->has_verifiedBVN ? 'Yes' : 'No',
            $user->has_default_bank_account ? 'Yes' : 'No',
            $user->has_withdrawn ? 'Yes' : 'No',
            $user->email_verified_at ? $user->email_verified_at->format('Y-m-d H:i:s') : 'Not Verified',
            $user->created_at->format('Y-m-d H:i:s'),
            $user->updated_at->format('Y-m-d H:i:s')
        ];
    }
    
    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row (headers) as bold
            1 => ['font' => ['bold' => true]],
        ];
    }
    
    /**
     * @return string
     */
    public function title(): string
    {
        return 'Users Export ' . date('Y-m-d');
    }
}