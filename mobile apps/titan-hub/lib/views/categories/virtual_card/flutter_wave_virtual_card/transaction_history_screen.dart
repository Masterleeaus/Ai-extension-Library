import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:qrpay/backend/utils/custom_loading_api.dart';
import 'package:qrpay/utils/dimensions.dart';
import 'package:qrpay/utils/responsive_layout.dart';
import 'package:qrpay/widgets/appbar/appbar_widget.dart';
import 'package:qrpay/widgets/bottom_navbar/transaction_history_widget.dart';

import '../../../../backend/utils/no_data_widget.dart';
import '../../../../controller/categories/virtual_card/flutter_wave_virtual_card/virtual_card_controller.dart';
import '../../../../language/english.dart';

class TransactionHistoryScreen extends StatelessWidget {
  TransactionHistoryScreen({super.key});

  final controller = Get.put(VirtualCardController());

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: Scaffold(
        appBar: const AppBarWidget(text: Strings.transactionHistory),
        body: Obx(
          () => controller.isLoading
              ? const CustomLoadingAPI()
              : _bodyWidget(context),
        ),
      ),
    );
  }

  _bodyWidget(BuildContext context) {
    final data = controller.cardTransactionModel.data.cardTransactions;
    return data.isNotEmpty
        ? ListView.builder(
            padding: EdgeInsets.symmetric(
              horizontal: Dimensions.marginSizeHorizontal * 0.9,
            ),
            physics: const BouncingScrollPhysics(),
            itemCount: data.length,
            itemBuilder: (context, index) {
              return TransactionWidget(
                amount: data[index].amount,
                title: data[index].paymentDetails,
                dateText: controller.getDay(data[index].date),
                transaction: data[index].trx.toString(),
                monthText: controller.getMonth(data[index].date),
              );
            })
        : const NoDataWidget();
  }
}
