import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:qrpay/utils/dimensions.dart';
import 'package:qrpay/utils/responsive_layout.dart';
import 'package:qrpay/widgets/appbar/appbar_widget.dart';
import 'package:qrpay/widgets/buttons/primary_button.dart';
import 'package:qrpay/widgets/others/preview/amount_preview_widget.dart';
import 'package:qrpay/widgets/others/preview/information_amount_widget.dart';
import 'package:qrpay/widgets/others/preview/recipient_preview_widget.dart';

import '../../../backend/utils/custom_loading_api.dart';
import '../../../controller/categories/remittance/remitance_controller.dart';
import '../../../language/english.dart';

class RemittancePreviewScreen extends StatelessWidget {
  RemittancePreviewScreen({super.key});

  final controller = Get.put(RemittanceController());

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
        mobileScaffold: Scaffold(
      appBar: const AppBarWidget(text: Strings.preview),
      body: _bodyWidget(context),
    ));
  }

  _bodyWidget(BuildContext context) {
    return ListView(
      padding: EdgeInsets.symmetric(horizontal: Dimensions.paddingSize * 0.8),
      physics: const BouncingScrollPhysics(),
      children: [
        _amountWidget(context),
        _recipientWidget(context),
        _amountInformationWidget(context),
        _buttonWidget(context),
      ],
    );
  }

  _amountWidget(BuildContext context) {
    return previewAmount(
        amount:
            "${controller.amountController.text} ${controller.baseCurrency}");
  }

  _recipientWidget(BuildContext context) {
    return previewRecipient(
      recipient: Strings.recipientInformation,
      name: controller.selectedRecipient.value,
      nameRow: controller.selectedMethod.value,
      subTitle: "",
      subTitleRow: "",
    );
  }

  _amountInformationWidget(BuildContext context) {
    var data = controller.remittanceInfoModel.data.remittanceCharge;
    double amount = double.parse(controller.amountController.text);
    double cardChare = double.parse(data.fixedCharge.toString());
    double percentCharge = (amount / 100) * data.percentCharge;
    double totalPayable = amount + (cardChare + percentCharge);
    return amountInformationWidget(
      information: Strings.amountInformation,
      enterAmount: Strings.enterAmount,
      enterAmountRow: "${amount.toString()} ${controller.baseCurrency}",
      fee: Strings.transferFee,
      feeRow:
          "${controller.totalFee.value.toStringAsFixed(4)} ${controller.baseCurrency}",
      received: Strings.recipientReceived,
      receivedRow:
          "${controller.recipientGetController.text.toString()} ${controller.selectedReceivingCountryCode.value} ",
      total: Strings.totalPayable,
      totalRow: "${totalPayable.toString()} ${controller.baseCurrency}",
    );
  }

  _buttonWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.only(
        top: Dimensions.marginSizeVertical * 2,
      ),
      child: Obx(
        () => controller.isRemittanceConfirm
            ? const CustomLoadingAPI()
            : PrimaryButton(
                title: Strings.confirm,
                onPressed: () {
                  controller.remittanceConfirmProcess(context);
                },
              ),
      ),
    );
  }
}
