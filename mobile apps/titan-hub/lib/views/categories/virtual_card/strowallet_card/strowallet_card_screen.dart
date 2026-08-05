import 'package:qrpay/widgets/others/glass_widget.dart';

import '../../../../backend/utils/custom_loading_api.dart';
import '../../../../backend/utils/custom_snackbar.dart';
import '../../../../controller/categories/virtual_card/strowallet_card/strowallelt_info_controller.dart';
import '../../../../custom_assets/assets.gen.dart';
import '../../../../routes/routes.dart';
import '../../../../utils/basic_screen_imports.dart';
import '../../../../widgets/bottom_navbar/categorie_widget.dart';
import '../../../../widgets/bottom_navbar/transaction_history_widget.dart';
import 'strowallet_slider.dart';

class StrowalletCardScreen extends StatelessWidget {
  const StrowalletCardScreen({super.key, required this.controller});
  final VirtualStrowalletCardController controller;
  @override
  Widget build(BuildContext context) {
    return Obx(
      () => controller.isLoading
          ? const CustomLoadingAPI()
          : _bodyWidget(context),
    );
  }

  _stroWalletCardWidget(BuildContext context) {
    var width = MediaQuery.of(context).size.width;
    return Stack(
      children: [
        Column(
          children: [
            Container(
              width: width,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(
                  Dimensions.radius * 2,
                ),
                color: CustomColor.whiteColor,
              ),
              margin: EdgeInsets.symmetric(
                horizontal: Dimensions.marginSizeHorizontal * 0.8,
              ),
              padding: EdgeInsets.symmetric(
                horizontal: Dimensions.paddingSize * 0.4,
                vertical: Dimensions.paddingSize * 0.4,
              ),
              child: Column(
                mainAxisAlignment: mainCenter,
                mainAxisSize: mainMin,
                children: [
                  StrowalletSlider(),
                  verticalSpace(Dimensions.heightSize * 0.5),
                  Visibility(
                    visible:
                        controller.strowalletCardModel.data.myCards.isNotEmpty,
                    child: GridView.count(
                        physics: const NeverScrollableScrollPhysics(),
                        scrollDirection: Axis.vertical,
                        crossAxisCount:
                            controller.strowalletCardModel.data.cardCreateAction
                                ? 5
                                : 4,
                        mainAxisSpacing: 10,
                        childAspectRatio: 0.5,
                        shrinkWrap: true,
                        children: [
                          CategoriesWidget(
                              icon: Assets.icon.details,
                              text: Strings.details,
                              onTap: () {
                                controller.getStrowalletCardData();
                                if (controller.strowalletCardModel.data.myCards
                                    .isNotEmpty) {
                                  Get.toNamed(
                                      Routes.strowalletCardDetailsScreen);
                                } else {
                                  CustomSnackBar.error(
                                      Strings.youDonNotBuyCard);
                                }
                              }),
                          CategoriesWidget(
                              icon: Assets.icon.fund,
                              text: Strings.fund,
                              onTap: () {
                                controller.getStrowalletCardData();
                                if (controller.strowalletCardModel.data.myCards
                                    .isNotEmpty) {
                                  Get.toNamed(Routes.strowalletAddFundScreen);
                                } else {
                                  CustomSnackBar.error(
                                    Strings.youDonNotBuyCard,
                                  );
                                }
                              }),
                          Obx(
                            () => controller.isMakeDefaultLoading
                                ? const CustomLoadingAPI()
                                : CategoriesWidget(
                                    icon: Assets.icon.torch,
                                    text: controller
                                            .strowalletCardModel
                                            .data
                                            .myCards[controller
                                                .dashboardController
                                                .current
                                                .value]
                                            .isDefault
                                        ? Strings.removeDefault
                                        : Strings.makeDefault,
                                    onTap: () {
                                      controller.makeCardDefaultProcess(
                                        controller
                                            .strowalletCardModel
                                            .data
                                            .myCards[controller
                                                .dashboardController
                                                .current
                                                .value]
                                            .cardId,
                                      );
                                    },
                                  ),
                          ),
                          CategoriesWidget(
                            icon: Assets.icon.transaction,
                            text: Strings.transaction,
                            onTap: () {
                              controller.getStrowalletCardData();
                              if (controller.strowalletCardModel.data.myCards
                                  .isNotEmpty) {
                                Get.toNamed(
                                    Routes.strowalletTransactionHistoryScreen);
                              } else {
                                CustomSnackBar.error(
                                  Strings.youDonNotBuyCard,
                                );
                              }
                            },
                          ),
                          if (controller
                              .strowalletCardModel.data.cardCreateAction) ...[
                            CategoriesWidget(
                              icon: Assets.icon.buyGift,
                              text: Strings.createCard,
                              onTap: () {
                                controller.customerStatusUpdate();
                                Get.toNamed(Routes.crateStrowalletScreen);
                              },
                            ),
                          ],
                        ]),
                  ),
                ],
              ),
            ),
            if (controller.strowalletCardModel.data.myCards.isEmpty) ...[
              _createCardWidget(context)
            ],
          ],
        ),
        _draggableSheet(context),
      ],
    );
  }

  Widget _bodyWidget(BuildContext context) {
    return _stroWalletCardWidget(context);
  }

  _draggableSheet(BuildContext context) {
    return DraggableScrollableSheet(
      builder: (_, scrollController) {
        return _recentTransWidget(context, scrollController);
      },
      initialChildSize: 0.45,
      minChildSize: 0.45,
      maxChildSize: 1,
    );
  }

  _recentTransWidget(BuildContext context, ScrollController scrollController) {
    final data = controller.strowalletCardModel.data.transactions;
    return Padding(
      padding: EdgeInsets.symmetric(horizontal: Dimensions.paddingSize * 0.8),
      child: data.isNotEmpty
          ? ListView(
              physics: const NeverScrollableScrollPhysics(),
              children: [
                CustomTitleHeadingWidget(
                  text: Strings.recentTransactions,
                  style: Get.isDarkMode
                      ? CustomStyle.darkHeading3TextStyle.copyWith(
                          fontSize: Dimensions.headingTextSize2,
                          fontWeight: FontWeight.w600,
                        )
                      : CustomStyle.lightHeading3TextStyle.copyWith(
                          fontSize: Dimensions.headingTextSize2,
                          fontWeight: FontWeight.w600,
                        ),
                ),
                verticalSpace(Dimensions.widthSize),
                SizedBox(
                  height: MediaQuery.of(context).size.height,
                  child: ListView.builder(
                    controller: scrollController,
                    physics: const BouncingScrollPhysics(),
                    itemCount: data.length,
                    itemBuilder: (context, index) {
                      return TransactionWidget(
                        amount: data[index].requestAmount,
                        title: data[index].transactinType,
                        dateText:
                            controller.getDay(data[index].dateTime.toString()),
                        transaction: data[index].trx,
                        monthText: controller
                            .getMonth(data[index].dateTime.toString()),
                      );
                    },
                  ),
                )
              ],
            ).customGlassWidget()
          : Container(),
    );
  }

  _createCardWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.symmetric(
        horizontal: Dimensions.marginSizeHorizontal,
        vertical: Dimensions.paddingSize * 2,
      ),
      padding: EdgeInsets.symmetric(vertical: Dimensions.paddingSize * .5),
      child: PrimaryButton(
        title: Strings.createCard.tr,
        onPressed: () {
          controller.customerStatusUpdate();
          Get.toNamed(Routes.crateStrowalletScreen);
        },
        borderColor: Theme.of(context).primaryColor,
        buttonColor: Theme.of(context).primaryColor,
      ),
    );
  }
}
