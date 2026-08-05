import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:qrpay/backend/utils/no_data_widget.dart';
import 'package:qrpay/utils/dimensions.dart';
import 'package:qrpay/utils/responsive_layout.dart';

import '../../backend/utils/custom_loading_api.dart';
import '../../controller/drawer/mobile_topup_controller.dart';
import '../../language/english.dart';
import '../../widgets/appbar/appbar_widget.dart';
import '../../widgets/bottom_navbar/transaction_history_widget.dart';

class MobileLogScreen extends StatelessWidget {
  MobileLogScreen({super.key});

  final controller = Get.put(MobileTopLogUpController());

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: Scaffold(
        appBar: const AppBarWidget(
          text: Strings.mobileTopUpLog,
        ),
        body: Obx(() => controller.isLoading
            ? const CustomLoadingAPI()
            : _bodyWidget(context)),
      ),
    );
  }

  _bodyWidget(BuildContext context) {
    return controller.transactioData.data.transactions.mobileTopUp.isNotEmpty
        ? ListView.builder(
            padding: EdgeInsets.symmetric(
              horizontal: Dimensions.marginSizeHorizontal * 0.9,
            ),
            physics: const BouncingScrollPhysics(),
            itemCount:
                controller.transactioData.data.transactions.mobileTopUp.length,
            itemBuilder: (context, index) {
              var data = controller
                  .transactioData.data.transactions.mobileTopUp[index];

              return TransactionWidget(
                amount: data.requestAmount,
                payable: data.payable,
                title: data.transactionType,
                dateText: data.dateTime.day.toString(),
                transaction: data.trx,
                monthText: "${data.dateTime.month}/${data.dateTime.year}",
              );
            })
        : const NoDataWidget();
  }
}
