import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:intl/intl.dart';

import '../../../backend/model/transaction_log/transaction_log_model.dart';
import '../../../backend/utils/no_data_widget.dart';
import '../../../controller/drawer/transaction_controller.dart';
import '../../../language/english.dart';
import '../../../utils/custom_color.dart';
import '../../../utils/dimensions.dart';
import '../../../utils/size.dart';
import '../../../widgets/bottom_navbar/transaction_history_widget.dart';
import '../../../widgets/expended_item_widget.dart';

class AgentTransactionLogScreen extends StatelessWidget {
  const AgentTransactionLogScreen({super.key, required this.controller});
  final TransactionController controller;

  @override
  Widget build(BuildContext context) {
    var data = controller.transactioData.data.transactions.agentMoneyOut;
    return SizedBox(
      height: MediaQuery.of(context).size.height,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            height: Dimensions.heightSize * 1.5,
          ),
          SizedBox(
            height: MediaQuery.of(context).size.height * 0.78,
            child: data.isNotEmpty
                ? ListView.separated(
                    physics: const BouncingScrollPhysics(),
                    padding: EdgeInsets.symmetric(
                      horizontal: Dimensions.paddingSize * .3,
                    ),
                    separatorBuilder: (_, index) => verticalSpace(4),
                    itemCount: data.length,
                    itemBuilder: (_, i) {
                      return _mainListWidget(i, data, context);
                    },
                  )
                : NoDataWidget(
                    title: Strings.noTransaction.tr,
                  ),
          ),
        ],
      ),
    );
  }

  _mainListWidget(int i, List<AgentMoneyOut> data, BuildContext context) {
    RxBool isExpansion = false.obs;
    return GestureDetector(
      onTap: () {
        isExpansion.value = !isExpansion.value;
      },
      child: Column(
        children: [
          TransactionWidget(
            status: data[i].status,
            amount: data[i].requestAmount,
            payable: data[i].payable,
            title: data[i].transactionType,
            dateText: DateFormat.d().format(data[i].dateTime),
            transaction: data[i].trx,
            monthText: DateFormat.MMM().format(data[i].dateTime),
          ),
          Obx(() => Visibility(
                visible: isExpansion.value,
                child: Container(
                  padding: EdgeInsets.all(Dimensions.paddingSize * .6),
                  decoration: BoxDecoration(
                    color: CustomColor.primaryLightColor.withOpacity(0.9),
                    borderRadius: BorderRadius.circular(Dimensions.radius),
                  ),
                  child: Column(
                    children: [
                      ExpendedItemWidget(
                        title: Strings.transactionId.tr,
                        value: data[i].trx,
                      ),
                      ExpendedItemWidget(
                        title: Strings.feesAndCharges.tr,
                        value: data[i].totalCharge,
                      ),
                      ExpendedItemWidget(
                        title: Strings.currentBalance.tr,
                        value: data[i].currentBalance,
                      ),
                      ExpendedItemWidget(
                        title: Strings.totalPayable.tr,
                        value: data[i].payable,
                      ),
                      ExpendedItemWidget(
                        title: Strings.timeAndDate.tr,
                        value:
                            DateFormat('yyyy-MM-dd').format(data[i].dateTime),
                      ),
                    ],
                  ),
                ),
              ))
        ],
      ),
    );
  }
}
