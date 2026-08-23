import 'package:qrpay/backend/local_storage/local_storage.dart';
import 'package:qrpay/controller/categories/virtual_card/flutter_wave_virtual_card/virtual_card_controller.dart';
import 'package:qrpay/controller/categories/virtual_card/strowallet_card/strowallelt_info_controller.dart';
import 'package:qrpay/utils/responsive_layout.dart';

import '../../../controller/categories/virtual_card/stripe_card/stripe_card_controller.dart';
import '../../../controller/categories/virtual_card/sudo_virtual_card/virtual_card_sudo_controller.dart';
import '../../../routes/routes.dart';
import '../../../utils/basic_screen_imports.dart';
import '../../../widgets/appbar/appbar_widget.dart';
import 'flutter_wave_virtual_card/flutter_wave_virtual_screen.dart';
import 'stripe_card/stripe_create_card_screen.dart';
import 'strowallet_card/strowallet_card_screen.dart';
import 'sudo_virtual_card/sudo_virtual_screen.dart';

class VirtualCardScreen extends StatelessWidget {
  VirtualCardScreen({super.key});

  final flutterWaveController = Get.put(VirtualCardController());
  final sudoController = Get.put(VirtualSudoCardController());
  // final dashboardController = Get.find<DashBoardController>();
  final stripeCardController = Get.put(StripeCardController());
  final strowalletCardController = Get.put(VirtualStrowalletCardController());

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: Scaffold(
        appBar: AppBarWidget(
          text: Strings.virtualCard,
          homeButtonShow: LocalStorages.getCardType() == 'sudo' ? true : false,
          onTapAction: () {
            if (LocalStorages.getCardType() == 'sudo') {
              Get.toNamed(Routes.sudoCreateCardScreen);
            } else if (LocalStorages.getCardType() == 'flutterwave') {
              Get.toNamed(Routes.buyCardScreen);
            }
          },
          actionIcon: Icons.add_circle_outline_outlined,
        ),
        body: LocalStorages.getCardType() == 'sudo'
            ? SudoVirtualCardScreen(controller: sudoController)
            : LocalStorages.getCardType() == 'stripe'
                ? StripeCreateCardScreen(
                    controller: stripeCardController,
                  )
                : LocalStorages.getCardType() == 'flutterwave'
                    ? FlutterWaveVirtualCardScreen(
                        controller: flutterWaveController,
                      )
                    : StrowalletCardScreen(
                        controller: strowalletCardController,
                      ),
      ),
    );
  }
}
