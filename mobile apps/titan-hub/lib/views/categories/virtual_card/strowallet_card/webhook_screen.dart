import 'package:intl/intl.dart';

import '../../../../backend/model/categories/virtual_card/strowallet_models/webhook_model.dart';
import '../../../../backend/utils/custom_loading_api.dart';
import '../../../../controller/categories/virtual_card/strowallet_card/strowallet_webhook_controller.dart';
import '../../../../utils/basic_screen_imports.dart';
import '../../../../widgets/appbar/appbar_widget.dart';
import '../../../../widgets/expended_item_widget.dart';
import '../../../../widgets/others/strowallet/webhook_transaction_widget.dart';

class WebhookLogsScreen extends StatelessWidget {
  WebhookLogsScreen({super.key});
  final controller = Get.put(StrowalletWebhookController());

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: const AppBarWidget(
        text: Strings.webhookLogs,
      ),
      body: Obx(() => controller.isLoading
          ? const CustomLoadingAPI()
          : _bodyWidget(context)),
    );
  }

  Widget _mainListWidget(int i, List<Transaction> data, BuildContext context,
      RxInt expandedIndex) {
    return GestureDetector(
      onTap: () {
        // Toggle the expansion state for the current index
        expandedIndex.value = expandedIndex.value == i ? -1 : i;
      },
      child: Column(
        children: [
          TransactionWebWidget(
            status: data[i].status,
            amount: data[i].amount,
            payableAmount: data[i].amount,
            title: data[i].event,
            transaction: data[i].transitionId,
          ),
          Obx(() => Visibility(
                visible: expandedIndex.value == i,
                child: Container(
                  padding: EdgeInsets.all(Dimensions.paddingSize * .6),
                  decoration: BoxDecoration(
                    color: CustomColor.primaryLightColor.withValues(alpha: 0.9),
                    borderRadius: BorderRadius.circular(Dimensions.radius),
                  ),
                  child: Column(
                    children: [
                      ExpendedItemWidget(
                        title: Strings.transactionId.tr,
                        value: data[i].transitionId,
                      ),
                      ExpendedItemWidget(
                        title: Strings.cardId.tr,
                        value: data[i].cardId,
                      ),
                      ExpendedItemWidget(
                        title: Strings.reference.tr,
                        value: data[i].reference,
                      ),
                      if (data[i].eventType ==
                              'virtualcard.transaction.declined' ||
                          data[i].eventType ==
                              'virtualcard.transaction.crossborder') ...[
                        ExpendedItemWidget(
                          title: Strings.narration.tr,
                          value: data[i].narrative ?? "N/A",
                        ),
                      ],
                      if (data[i].eventType ==
                          'virtualcard.transaction.declined') ...[
                        ExpendedItemWidget(
                          title: Strings.reason.tr,
                          value: data[i].reason ?? "N/A",
                        ),
                      ],
                      ExpendedItemWidget(
                        title: Strings.status.tr,
                        value: data[i].status,
                      ),
                      if (data[i].eventType ==
                          'virtualcard.transaction.crossborder') ...[
                        ExpendedItemWidget(
                          title: Strings.chargedAmount.tr,
                          value: data[i].chargeAmount ?? "N/A",
                        ),
                      ],
                      if (data[i].eventType ==
                          'virtualcard.transaction.declined.terminated') ...[
                        ExpendedItemWidget(
                          title: Strings.balanceBeforeTermination.tr,
                          value: data[i].balanceBeforeTermination ?? "N/A",
                        ),
                      ],
                      if (data[i].eventType ==
                              'virtualcard.transaction.declined' ||
                          data[i].eventType ==
                              'virtualcard.transaction.crossborder') ...[
                        ExpendedItemWidget(
                          title: Strings.timeAndDate.tr,
                          value: data[i].createdAt == null ||
                                  data[i].createdAt!.trim().isEmpty
                              ? "N/A"
                              : DateFormat("d-M-yy hh:mm:ss a")
                                  .format(DateTime.parse(data[i].createdAt!)),
                        ),
                      ]
                    ],
                  ),
                ),
              )),
        ],
      ),
    );
  }

  Widget _bodyWidget(BuildContext context) {
    var data = controller.webhookLogModel.data.transactions;

    // State management for expanded index
    final RxInt expandedIndex = (-1).obs;

    return ListView(
      children: [
        SizedBox(
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
                          return _mainListWidget(
                              i, data, context, expandedIndex);
                        },
                      )
                    : Center(
                        child: TitleHeading3Widget(
                          textAlign: TextAlign.center,
                          text: Strings.noTransaction,
                          color: CustomColor.primaryLightColor,
                        ),
                      ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
