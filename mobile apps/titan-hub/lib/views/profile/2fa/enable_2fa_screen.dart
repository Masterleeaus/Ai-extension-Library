import 'package:flutter/services.dart';

import '/language/language_controller.dart';
import '/widgets/appbar/appbar_widget.dart';
import '../../../backend/utils/custom_loading_api.dart';
import '../../../backend/utils/custom_snackbar.dart';
import '../../../controller/profile/twoFa/enable_twofa_controller.dart';
import '../../../utils/basic_screen_imports.dart';
import '../../../widgets/text_labels/title_subtitle_widget.dart';

class Enable2FaScreen extends StatelessWidget {
  Enable2FaScreen({super.key});

  final controller = Get.put(TwoFaController());
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: const AppBarWidget(
        text: "",
      ),
      body: Obx(
        () => controller.isLoading
            ? const CustomLoadingAPI()
            : _bodyWidget(context),
      ),
    );
  }

  _bodyWidget(BuildContext context) {
    return Column(
      mainAxisAlignment: mainStart,
      children: [
        _titleSubTitleWidget(context),
        _qrCodeWidget(context),
        _qrSecret(),
        _enableButtonWidget(context),
      ],
    ).paddingSymmetric(horizontal: Dimensions.marginSizeHorizontal * 0.8);
  }

  _enableButtonWidget(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(top: Dimensions.marginSizeVertical * 0.3),
      child: Obx(
        () => controller.isSubmitLoading
            ? const CustomLoadingAPI()
            : PrimaryButton(
                title: controller.status.value == 0
                    ? Strings.enable
                    : Strings.disable,
                onPressed: () {
                  controller.twoFaSubmitApiProcess();
                },
              ),
      ),
    );
  }

  _titleSubTitleWidget(BuildContext context) {
    return TitleSubTitleWidget(
      title: Strings.enable2FASecurity,
      subtitle: controller.alert.value,
      crossAxisAlignment: crossCenter,
    );
  }

  _qrCodeWidget(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(15),
      decoration: BoxDecoration(
        border: Border.all(
          width: 2,
          color: CustomColor.primaryLightColor,
        ),
        borderRadius: BorderRadius.circular(
          10,
        ),
      ),
      margin: EdgeInsets.all(Dimensions.paddingSize * 0.4),
      child: Image.network(
        controller.qrCode.value,
        scale: 1.2,
      ).paddingOnly(bottom: Dimensions.marginSizeVertical * 0.5),
    );
  }

  _qrSecret() {
    return Column(
      children: [
        PrimaryInputWidget(
          hint: "",
          readOnly: true,
          paddings: EdgeInsets.only(
            left: Dimensions.widthSize,
            right: Dimensions.widthSize,
            // top: Dimensions.heightSize,
          ),
          controller: controller.qrSecretController,
          suffixIcon: InkWell(
            onTap: () {
              Clipboard.setData(
                      ClipboardData(text: controller.qrSecretController.text))
                  .then((_) {
                CustomSnackBar.success(Get.find<LanguageController>()
                    .getTranslation(Strings.addressCopyTo));
              });
            },
            child: Container(
              padding: EdgeInsets.symmetric(
                vertical: Dimensions.paddingSize * 0.8,
                horizontal: Dimensions.paddingSize * 0.5,
              ),
              decoration: BoxDecoration(
                borderRadius: BorderRadius.only(
                  topRight: Radius.circular(Dimensions.radius * 1.2),
                  bottomRight: Radius.circular(Dimensions.radius * 1.2),
                ),
                color: CustomColor.primaryLightColor,
              ),
              child: const Icon(
                Icons.copy,
                color: Colors.white,
              ),
            ),
          ),
          label: Strings.address,
        ),
        verticalSpace(Dimensions.paddingVerticalSize * 0.5)
      ],
    );
  }
}
