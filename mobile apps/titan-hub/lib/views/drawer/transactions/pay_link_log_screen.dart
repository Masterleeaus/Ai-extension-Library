import 'package:intl/intl.dart';

import '../../../backend/model/transaction_log/transaction_log_model.dart';
import '../../../backend/utils/no_data_widget.dart';
import '../../../controller/drawer/transaction_controller.dart';
import '../../../utils/basic_screen_imports.dart';
import '../../../widgets/bottom_navbar/transaction_history_widget.dart';
import '../../../widgets/expended_item_widget.dart';

class PayLinkLogScreen extends StatelessWidget {
  const PayLinkLogScreen({super.key, required this.controller});
  final TransactionController controller;

  @override
  Widget build(BuildContext context) {
    var data = controller.transactioData.data.transactions.payLink;
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

  _mainListWidget(int i, List<PayPayLink> data, BuildContext context) {
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
                        title: Strings.exchangeRate.tr,
                        value: data[i].exchangeRate,
                      ),
                      ExpendedItemWidget(
                        title: Strings.feesAndCharges.tr,
                        value: data[i].totalCharge,
                      ),
                      ExpendedItemWidget(
                        title: Strings.currentBalance.tr,
                        value: data[i].currentBalance,
                      ),
                      if (data[i].paymentType == "wallet_payment") ...[
                        ExpendedItemWidget(
                          title: Strings.paymentType.tr,
                          value: data[i].paymentType,
                        ),
                        ExpendedItemWidget(
                          title: Strings.senderEmail.tr,
                          value: data[i].paymentTypeWalletData.senderEmail,
                        ),
                      ],
                      if (data[i].paymentType == "payment_gateway") ...[
                        ExpendedItemWidget(
                          title: Strings.paymentType.tr,
                          value: data[i].paymentType,
                        ),
                        ExpendedItemWidget(
                          title: Strings.paymentGateway.tr,
                          value: data[i].paymentTypeGatewayData.paymentGateway,
                        ),
                      ],
                      if (data[i].paymentType == "card_payment") ...[
                        ExpendedItemWidget(
                          title: Strings.paymentType.tr,
                          value: data[i].paymentType,
                        ),
                        ExpendedItemWidget(
                          title: Strings.senderEmail.tr,
                          value: data[i].paymentTypeCardData.senderEmail,
                        ),
                        ExpendedItemWidget(
                          title: Strings.cardHolderName.tr,
                          value: data[i].paymentTypeCardData.cardHolderName,
                        ),
                        ExpendedItemWidget(
                          title: Strings.senderCardNumber.tr,
                          value:
                              '**** **** **** ${data[i].paymentTypeCardData.senderCardLast4}',
                        ),
                      ],
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
