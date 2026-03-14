<?php //>

namespace MatrixPlatform\Support;

use MatrixPlatform\Models\Currency;
use MatrixPlatform\Models\FrozenLog;
use MatrixPlatform\Models\Wallet;
use MatrixPlatform\Models\WalletLog;

class Wallets {

    public static function freeze($wallet, $type, $amount, $remark, $data) {
        $amount = round($amount, $wallet->currency->precision);

        if (!$amount) {
            error('invalid-frozen-amount');
        }

        $frozen = round($wallet->frozen + $amount, $wallet->currency->precision);

        if ($wallet->balance < $frozen || $frozen < 0) {
            error('invalid-frozen-amount');
        }

        $wallet->frozen = $frozen;
        $wallet->save();

        $log = new FrozenLog();
        $log->wallet_id = $wallet->id;
        $log->the_date = date('Y-m-d');
        $log->type = $type;
        $log->amount = $amount;
        $log->remark = $remark;
        $log->data = $data;
        $log->save();

        return $log;
    }

    public static function get($member, $currency) {
        if (is_string($currency)) {
            $currency = Currency::where('code', $currency)->firstOrFail();
        }

        $wallet = Wallet::where(['member_id' => $member->id, 'currency_id' => $currency->id])->first();

        return self::resolve($wallet, $member, $currency);
    }

    public static function list($member) {
        $wallets = Wallet::where('member_id', $member->id)->get()->keyBy('currency_id');

        return Currency::whereActive()
            ->orderBy('ranking')
            ->get()
            ->map(fn ($currency) => self::resolve($wallets->get($currency->id), $member, $currency));
    }

    public static function manipulate($wallet, $type, $amount, $remark, $data) {
        $amount = round($amount, $wallet->currency->precision);

        if (!$amount) {
            error('invalid-manipulation-amount');
        }

        $balance = round($wallet->balance + $amount, $wallet->currency->precision);

        if ($balance < $wallet->frozen) {
            error('insufficient-balance');
        }

        $wallet->balance = $balance;
        $wallet->save();

        $log = new WalletLog();
        $log->wallet_id = $wallet->id;
        $log->the_date = date('Y-m-d');
        $log->type = $type;
        $log->amount = $amount;
        $log->balance = $balance;
        $log->remark = $remark;
        $log->data = $data;
        $log->save();

        return $log;
    }

    private static function resolve($wallet, $member, $currency) {
        if (!$wallet) {
            $wallet = new Wallet();
            $wallet->member_id = $member->id;
            $wallet->currency_id = $currency->id;
            $wallet->save();
        }

        $wallet->setRelation('member', $member);
        $wallet->setRelation('currency', $currency);

        return $wallet;
    }

}
