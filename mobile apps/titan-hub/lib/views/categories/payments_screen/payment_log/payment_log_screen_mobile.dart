import 'package:flutter_staggered_animations/flutter_staggered_animations.dart';
import 'package:intl/intl.dart';

import '../../../../backend/services/api_endpoint.dart';
import '../../../../backend/utils/custom_loading_api.dart';
import '../../../../backend/utils/no_data_widget.dart';
import '../../../../controller/categories/payments/payments_controller.dart';
import '../../../../routes/routes.dart';
import '../../../../utils/basic_screen_imports.dart';
import '../../../../widgets/animation/custom_listview_animation.dart';
import '../../../../widgets/appbar/appbar_widget.dart';
import '../../../../widgets/payment_link/payment_log_widget.dart';

class PaymentLogScreenMobile extends StatelessWidget {
  PaymentLogScreenMobile({super.key});
  final controller = Get.put(PaymentsController());

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: true,
      onPopInvokedWithResult: (bool didPop, Object? result) {
        if (didPop) {
          Get.offAllNamed(Routes.bottomNavBarScreen);
        }
      },
      child: Scaffold(
        appBar: AppBarWidget(
          text: Strings.payments,
          onTapLeading: () {
            Get.offAllNamed(Routes.bottomNavBarScreen);
          },
        ),
        floatingActionButton: Obx(() {
          return Visibility(
            visible: !controller.isLoading,
            child: FloatingActionButton(
              elevation: 3,
              backgroundColor: CustomColor.primaryLightColor,
              shape: const CircleBorder(),
              onPressed: () {
                controller.typeSelection.value =
                    controller.typeSelectionList.first.title;
                controller.onPayments;
              },
              child: Icon(
                Icons.add,
                size: Dimensions.iconSizeLarge,
                color: Colors.white,
              ),
            ),
          );
        }),
        body: Obx(
          () => controller.isLoading || controller.isStatusLoading
              ? const CustomLoadingAPI()
              : _bodyWidget(context),
        ),
      ),
    );
  }

  _bodyWidget(BuildContext context) {
    var data = controller.paymentLinkModel.data.paymentLinks;
    return data.isNotEmpty
        ? RefreshIndicator(
            color: CustomColor.primaryLightColor,
            onRefresh: () async {
              controller.getPaymentLinkProcess();
            },
            child: Column(
              children: [
                AnimationLimiter(
                  child: Expanded(
                    child: ListView.builder(
                      physics: const BouncingScrollPhysics(
                        parent: AlwaysScrollableScrollPhysics(),
                      ),
                      itemCount: data.length,
                      itemBuilder: (context, index) {
                        return CustomListViewAnimation(
                          index: index,
                          child: PaymentLogWidget(
                            title: data[index].title,
                            dateText: data[index].createdAt.day.toString(),
                            status: data[index].stringStatus,
                            monthText:
                                DateFormat("MMM").format(data[index].createdAt),
                            amount: data[index].type == "pay" &&
                                    data[index].limit == 2
                                ? Strings.unlimited.tr
                                : data[index].limit == 1
                                    ? "${data[index].minAmount} - ${data[index].maxAmount} ${data[index].currency}"
                                    : data[index].type == "sub"
                                        ? "${data[index].price}(${data[index].qty}) ${data[index].currency}"
                                        : "${data[index].price} ${data[index].currency}",
                            onEditTap: () {
                              Navigator.pop(context);
                              Get.toNamed(Routes.paymentsEditScreen,
                                  arguments: data[index].id);
                            },
                            onCopyTap: () {
                              Navigator.pop(context);
                              controller.copyLinkController.text =
                                  "${ApiEndpoint.mainDomain}/payment-link/share/${data[index].token}";

                              controller.onCopyTap;
                            },
                            onActiveTap: () {
                              Navigator.pop(context);
                              controller.updateStatusProcess(
                                  id: data[index].id);
                            },
                            onCloseTap: () {
                              Navigator.pop(context);
                              controller.updateStatusProcess(
                                  id: data[index].id);
                            },
                          ).paddingSymmetric(
                            horizontal: Dimensions.paddingHorizontalSize * .6,
                          ),
                        );
                      },
                    ),
                  ),
                ),
              ],
            ),
          )
        : NoDataWidget(title: Strings.noPaymentCreatedYet.tr);
  }
}
