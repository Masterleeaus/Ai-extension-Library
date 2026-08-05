import 'package:flutter/material.dart';
import 'package:flutter_html/flutter_html.dart';
import 'package:get/get.dart';

import '../../../backend/utils/custom_loading_api.dart';
import '../../../controller/categories/withdraw_controller/withdraw_controller.dart';
import '../../../language/english.dart';
import '../../../routes/routes.dart';
import '../../../utils/custom_color.dart';
import '../../../utils/dimensions.dart';
import '../../../widgets/appbar/appbar_widget.dart';
import '../../../widgets/buttons/primary_button.dart';
import '../../../widgets/others/congratulation_widget.dart';

class WithdrawManualPaymentScreen extends StatelessWidget {
  WithdrawManualPaymentScreen({super.key});

  final controller = Get.put(WithdrawController());
  final formKey = GlobalKey<FormState>();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: const AppBarWidget(text: Strings.preview),
      body: Obx(
        () => controller.isLoading
            ? const CustomLoadingAPI()
            : _bodyWidget(context),
      ),
    );
  }

  _bodyWidget(BuildContext context) {
    return Padding(
      padding: EdgeInsets.symmetric(
        horizontal: Dimensions.paddingSize * 0.7,
        vertical: Dimensions.paddingSize * 0.7,
      ),
      child: Form(
        key: formKey,
        child: ListView(
          physics: const BouncingScrollPhysics(),
          children: [
            _descriptionWidget(context),
            ...controller.inputFields.map((element) {
              return element;
              // ignore: unnecessary_to_list_in_spreads
            }).toList(),
            _buttonWidget(context)
          ],
        ),
      ),
    );
  }

  _buttonWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.symmetric(vertical: Dimensions.marginSizeVertical),
      child: Obx(() => controller.isConfirmManualLoading
          ? const CustomLoadingAPI()
          : PrimaryButton(
              title: Strings.payNow.tr,
              onPressed: () {
                if (formKey.currentState!.validate()) {
                  controller.manualPaymentProcess().then((value) {
                    if (context.mounted) {
                      StatusScreen.show(
                        context: context,
                        subTitle: Strings.yourmoneyWithdrawSuccess.tr,
                        onPressed: () {
                          Get.offAllNamed(Routes.bottomNavBarScreen);
                        },
                      );
                    }
                  });
                }
              },
            )),
    );
  }

  _descriptionWidget(BuildContext context) {
    final data = controller.moneyOutManualInsertModel.data;
    return Container(
      padding: EdgeInsets.symmetric(
          vertical: Dimensions.paddingSize * 0.5,
          horizontal: Dimensions.paddingSize * 0.2),
      margin:
          EdgeInsets.symmetric(vertical: Dimensions.marginSizeVertical * 0.4),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(Dimensions.radius),
        border: Border.all(
          width: 0.8,
          color: CustomColor.primaryLightColor,
        ),
      ),
      child: Html(
        data: data.details,
      ),
    );
  }
}
