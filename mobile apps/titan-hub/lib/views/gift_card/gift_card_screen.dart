import 'package:qrpay/backend/utils/custom_loading_api.dart';

import '../../../utils/basic_screen_imports.dart';
import '../../../utils/responsive_layout.dart';
import '../../controller/gift_card/gift_card_controller.dart';
import '../../routes/routes.dart';
import '../../widgets/appbar/appbar_widget.dart';
import '../../widgets/gift_card/gift_card_log.dart';

class GiftCardScreen extends StatelessWidget {
  GiftCardScreen({super.key});
  final controller = Get.put(GiftCardController());
  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: SafeArea(
        child: Scaffold(
          appBar: AppBarWidget(
            text: Strings.giftCard.tr,
          ),
          floatingActionButton: _giftCardAddButton(context),
          body: Obx(
            () => controller.isLoading
                ? const CustomLoadingAPI()
                : _bodyWidget(context),
          ),
        ),
      ),
    );
  }

  _bodyWidget(BuildContext context) {
    return RefreshIndicator(
      onRefresh: () async {
        controller.getMyCardInfoApi();
      },
      child: ListView(
        padding: EdgeInsets.symmetric(
          horizontal: Dimensions.marginSizeHorizontal * 0.6,
        ),
        children: List.generate(
          controller.myGiftCardModel.data.giftCards.length,
          (index) => GiftCardLog(
            giftCard: controller.myGiftCardModel.data.giftCards[index],
          ),
        ),
      ),
    );
  }

  _giftCardAddButton(BuildContext context) {
    return FloatingActionButton(
      onPressed: () {
        Get.toNamed(Routes.allGiftCardScreen);
      },
      backgroundColor: CustomColor.primaryLightColor,
      child: const Icon(
        Icons.add_rounded,
        color: Colors.white,
      ),
    );
  }
}
