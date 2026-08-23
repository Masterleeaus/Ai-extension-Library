import 'package:flutter/services.dart';
import 'package:qrpay/utils/basic_screen_imports.dart';
import 'package:qrpay/utils/responsive_layout.dart';
import 'package:qrpay/widgets/appbar/appbar_widget.dart';
import 'package:qrpay/widgets/inputs/copy_with_input.dart';
import 'package:qrpay/widgets/text_labels/title_heading5_widget.dart';
import 'package:share_plus/share_plus.dart';

import '../../../backend/utils/custom_loading_api.dart';
import '../../../controller/categories/money_receiver_controller/money_receiver_controller.dart';
import '../../../custom_assets/assets.gen.dart';
import '../../../language/language_controller.dart';

class MoneyReceiveScreen extends StatelessWidget {
  MoneyReceiveScreen({super.key});

  final controller = Get.put(MoneyReceiverController());

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: Scaffold(
        appBar: const AppBarWidget(text: Strings.moneyReceive),
        body: Obx(() => controller.isLoading
            ? const CustomLoadingAPI()
            : _bodyWidget(context)),
      ),
    );
  }

  _bodyWidget(BuildContext context) {
    return ListView(
      padding: EdgeInsets.symmetric(
          horizontal: Dimensions.marginSizeHorizontal * 0.9),
      physics: const BouncingScrollPhysics(),
      children: [
        _imgWidget(context),
        _inputWidget(context),
        _buttonWidget(context),
      ],
    );
  }

  _imgWidget(BuildContext context) {
    return Container(
      padding: EdgeInsets.symmetric(
        horizontal: Dimensions.paddingSize,
        vertical: Dimensions.paddingSize * 1,
      ),
      decoration: BoxDecoration(
          color: CustomColor.whiteColor,
          borderRadius: BorderRadius.circular(Dimensions.radius * 1.5)),
      alignment: Alignment.center,
      height: MediaQuery.of(context).size.height * 0.3,
      margin: EdgeInsets.symmetric(
        vertical: Dimensions.marginSizeVertical * 1.4,
        horizontal: Dimensions.marginSizeHorizontal * 1.2,
      ),
      child: SizedBox(
        width: Dimensions.widthSize * 24,
        height: Dimensions.heightSize * 22,
        child: controller.receiveMoneyModel.data.qrCode != ''
            ? Image.network(controller.receiveMoneyModel.data.qrCode)
            : const Text(''),
      ),
    );
  }

  _inputWidget(BuildContext context) {
    return Column(
      crossAxisAlignment: crossStart,
      children: [
        IgnorePointer(
          ignoring: true,
          child: CopyInputWidget(
              suffixIcon: Assets.icon.copy,
              onTap: () {
                Clipboard.setData(
                  ClipboardData(text: controller.inputController.text),
                ).then(
                  (_) {
                    if (context.mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(
                          content: Text("Qr Address copied to clipboard"),
                        ),
                      );
                    }
                  },
                );
              },
              controller: controller.inputController,
              hint: Strings.qrCode,
              label: Strings.qrAddress),
        ),
        verticalSpace(Dimensions.heightSize),
        TitleHeading5Widget(
          text:
              '${Get.find<LanguageController>().getTranslation(Strings.use)} ${Strings.appName} ${Get.find<LanguageController>().getTranslation(Strings.useAppForInstant)}',
          textAlign: TextAlign.justify,
        )
      ],
    );
  }

  _buttonWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.only(
        top: Dimensions.marginSizeVertical * 4,
        bottom: Dimensions.marginSizeVertical * 0.4,
      ),
      child: PrimaryButton(
          title: Strings.share,
          onPressed: () {
            Share.share(controller.receiveMoneyModel.data.qrCode);
          }),
    );
  }
}
