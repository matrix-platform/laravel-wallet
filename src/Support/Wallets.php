<?php //>

namespace MatrixPlatform\Support;

use MatrixPlatform\Models\Currency;
use MatrixPlatform\Models\Wallet;

class Wallets {

    public static function get($member, $currency) {
        $wallet = Wallet::where(['member_id' => $member->id, 'currency_id' => $currency->id])->first();

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

    public static function list($member) {
        return Currency::whereActive()
            ->orderBy('ranking')
            ->get()
            ->map(fn ($currency) => self::get($member, $currency));
    }

}
