import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:qrpay/backend/utils/custom_loading_api.dart';
import 'package:qrpay/utils/dimensions.dart';
import 'package:qrpay/utils/responsive_layout.dart';
import 'package:qrpay/widgets/appbar/appbar_widget.dart';
import 'package:qrpay/widgets/buttons/primary_button.dart';
import 'package:qrpay/widgets/others/preview/amount_preview_widget.dart';
import 'package:qrpay/widgets/others/preview/information_amount_widget.dart';

import '../../../controller/categories/request_money/request_money_controller.dart';
import '../../../language/english.dart';
import '../../../routes/routes.dart';
import '../../../widgets/others/congratulation_widget.dart';

class RequestMoneyPreviewScreen extends StatelessWidget {
  RequestMoneyPreviewScreen({super.key});

  final controller = Get.put(RequestMoneyController());

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: Scaffold(
        appBar: const AppBarWidget(text: Strings.preview),
        body: _bodyWidget(context),
      ),
    );
  }

  _bodyWidget(BuildContext context) {
    return ListView(
      padding: EdgeInsets.symmetric(horizontal: Dimensions.paddingSize * 0.8),
      physics: const BouncingScrollPhysics(),
      children: [
        _amountWidget(context),
        _amountInformationWidget(context),
        _buttonWidget(context),
      ],
    );
  }

  _amountWidget(BuildContext context) {
    return previewAmount(
        amount:
            '${controller.amountController.text} ${controller.baseCurrency.value}');
  }

  _amountInformationWidget(BuildContext context) {
    return amountInformationWidget(
      information: Strings.amountInformation,
      enterAmount: Strings.enterAmount,
      enterAmountRow:
          '${controller.amountController.text} ${controller.baseCurrency.value}',
      fee: Strings.transferFee,
      feeRow:
          '${controller.totalFee.value.toStringAsFixed(2)} ${controller.baseCurrency.value}',
      received: Strings.recipientReceived,
      receivedRow:
          '${controller.amountController.text} ${controller.baseCurrency.value}',
      total: Strings.totalPayable,
      totalRow:
          '${(double.parse(controller.amountController.text.isNotEmpty ? controller.amountController.text : '0.0') + controller.totalFee.value)} ${controller.baseCurrency.value}',
    );
  }

  _buttonWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.only(
        top: Dimensions.marginSizeVertical * 2,
      ),
      child: Obx(
        () => controller.isRequestMoneyLoading
            ? const CustomLoadingAPI()
            : PrimaryButton(
                title: Strings.confirm,
                onPressed: () {
                  controller.requestMoneyProcess().then((value) {
                    if (context.mounted) {
                      StatusScreen.show(
                        context: context,
                        subTitle: Strings.requestMoneySuccess,
                        showAppName: false,
                        onPressed: () {
                          Get.offAllNamed(Routes.bottomNavBarScreen);
                        },
                      );
                    }
                  });
                },
              ),
      ),
    );
  }
}
