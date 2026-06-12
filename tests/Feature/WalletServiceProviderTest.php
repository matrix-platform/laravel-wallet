<?php //>

namespace Tests\Feature;

use MatrixPlatform\Models\Member;
use MatrixPlatform\Support\PackageInfo;
use Tests\FeatureTestCase;
use Tests\Models\TestMember;

class WalletServiceProviderTest extends FeatureTestCase {

    public function test_package_info_singleton_is_bound() {
        $info = app('PackageInfo:wallet');

        $this->assertInstanceOf(PackageInfo::class, $info);
        $this->assertSame($info, app('PackageInfo:wallet'));
    }

    public function test_wallet_config_is_merged() {
        $this->assertSame(TestMember::class, config('wallet.member-model'));
    }

    public function test_wallet_member_model_falls_back_to_member_when_unset() {
        config(['wallet.member-model' => null]);
        $merged = require __DIR__ . '/../../config/wallet.php';

        $this->assertSame(Member::class, $merged['member-model']);
    }

}
