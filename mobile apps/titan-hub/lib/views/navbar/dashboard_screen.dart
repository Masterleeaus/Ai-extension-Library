import 'package:intl/intl.dart';
import 'package:qrpay/backend/utils/custom_loading_api.dart';
import 'package:qrpay/controller/navbar/dashboard_controller.dart';
import 'package:qrpay/utils/basic_screen_imports.dart';
import 'package:qrpay/utils/responsive_layout.dart';
import 'package:qrpay/widgets/bottom_navbar/categorie_widget.dart';
import 'package:qrpay/widgets/others/custom_glass/custom_glass_widget.dart';

import '../../backend/model/bottom_navbar_model/dashboard_model.dart';
import '../../backend/utils/no_data_widget.dart';
import '../../custom_assets/assets.gen.dart';
import '../../widgets/bottom_navbar/transaction_history_widget.dart';

class DashboardScreen extends StatelessWidget {
  DashboardScreen({super.key});

  final controller = Get.find<DashBoardController>();

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: Scaffold(body: _bodyWidget(context)),
    );
  }

  _bodyWidget(BuildContext context) {
    return StreamBuilder<DashboardModel>(
      stream: controller.getDashboardDataStream(),
      builder: (context, snapshot) {
        if (snapshot.hasError) {
          return const Center(child: Text(Strings.somethingWentWrong));
        }
        if (snapshot.hasData) {
          return Stack(
            children: [
              ListView(
                physics: const NeverScrollableScrollPhysics(),
                children: [
                  _appBarContainer(context),
                  _categoriesWidget(context),
                ],
              ),
              _draggableSheet(context)
            ],
          );
        }
        return const Align(
          alignment: Alignment.center,
          child: CustomLoadingAPI(),
        );
      },
    );
  }

  _draggableSheet(BuildContext context) {
    bool isTablet() {
      return MediaQuery.of(context).size.shortestSide >= 600;
    }

    return DraggableScrollableSheet(
      builder: (_, scrollController) {
        return _transactionWidget(context, scrollController);
      },
      initialChildSize: isTablet() ? 0.60 : 0.50,
      minChildSize: isTablet() ? 0.60 : 0.50,
      maxChildSize: 1,
    );
  }

  _appBarContainer(BuildContext context) {
    var data = controller.dashBoardModel.data.userWallet;

    return Container(
      alignment: Alignment.center,
      height: MediaQuery.of(context).size.height * 0.17,
      decoration: BoxDecoration(
        color: CustomColor.primaryLightColor,
        borderRadius: BorderRadius.only(
          bottomLeft: Radius.circular(Dimensions.radius * 2),
          bottomRight: Radius.circular(Dimensions.radius * 2),
        ),
      ),
      child: Column(
        mainAxisAlignment: mainCenter,
        children: [
          CustomTitleHeadingWidget(
            text: "${data.balance.toString()} ${data.currency}",
            style: CustomStyle.darkHeading1TextStyle.copyWith(
              fontSize: Dimensions.headingTextSize4 * 2,
              fontWeight: FontWeight.w800,
              color: CustomColor.whiteColor,
            ),
          ),
          CustomTitleHeadingWidget(
            text: Strings.currentBalance,
            style: CustomStyle.lightHeading4TextStyle.copyWith(
              fontSize: Dimensions.headingTextSize3,
              color: CustomColor.whiteColor.withOpacity(0.6),
            ),
          ),
        ],
      ),
    );
  }

  _categoriesWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.only(
        top: Dimensions.marginSizeVertical * 0.5,
        bottom: Dimensions.marginSizeVertical * 0.8,
        right: Dimensions.marginSizeVertical * 0.4,
        left: Dimensions.marginSizeVertical * 0.2,
      ),
      child: GridView.count(
        padding: const EdgeInsets.only(),
        physics: const NeverScrollableScrollPhysics(),
        scrollDirection: Axis.vertical,
        crossAxisCount: 4,
        crossAxisSpacing: 4.0,
        shrinkWrap: true,
        childAspectRatio: 0.8,
        children: List.generate(
            controller.categoriesData.length > 8
                ? 8
                : controller.categoriesData.length, (index) {
          if (controller.categoriesData.length > 8 && index == 7) {
            return CategoriesWidget(
              onTap: () {
                showModalBottomSheet(
                  context: context,
                  backgroundColor: Theme.of(context).scaffoldBackgroundColor,
                  builder: (BuildContext context) {
                    return Padding(
                      padding: EdgeInsets.only(
                        left: Dimensions.marginSizeHorizontal,
                        right: Dimensions.marginSizeVertical,
                      ),
                      child: Column(
                        mainAxisSize: mainMin,
                        children: [
                          Container(
                            width: Dimensions.widthSize * 4.5,
                            height: Dimensions.heightSize * 0.55,
                            margin: EdgeInsets.only(
                              top: Dimensions.heightSize,
                              bottom: Dimensions.heightSize * 1.5,
                            ),
                            decoration: BoxDecoration(
                              borderRadius: BorderRadius.circular(
                                Dimensions.radius,
                              ),
                              color: Colors.grey.shade400,
                            ),
                          ),
                          GridView.count(
                            padding: const EdgeInsets.only(),
                            physics: const NeverScrollableScrollPhysics(),
                            scrollDirection: Axis.vertical,
                            crossAxisCount: 4,
                            crossAxisSpacing: 4.0,
                            mainAxisSpacing: 10.0,
                            childAspectRatio: 0.8,
                            shrinkWrap: true,
                            children: List.generate(
                              controller.categoriesData.length - 7,
                              (index) {
                                final adjustedIndex = index + 7;
                                return CategoriesWidget(
                                  onTap: controller
                                      .categoriesData[adjustedIndex].onTap,
                                  icon: controller
                                      .categoriesData[adjustedIndex].icon,
                                  text: controller
                                      .categoriesData[adjustedIndex].text,
                                );
                              },
                            ),
                          ),
                          verticalSpace(Dimensions.heightSize)
                        ],
                      ),
                    );
                  },
                );
              },
              icon: Assets.icon.more,
              text: Strings.more,
            );
          }
          return CategoriesWidget(
            onTap: controller.categoriesData[index].onTap,
            icon: controller.categoriesData[index].icon,
            text: controller.categoriesData[index].text,
          );
        }),
      ),
    );
  }

  _transactionWidget(BuildContext context, ScrollController scrollController) {
    var data = controller.dashBoardModel.data.transactions;
    return data.isEmpty
        ? NoDataWidget(
            title: Strings.noTransaction.tr,
          )
        : ListView(
            padding:
                EdgeInsets.symmetric(horizontal: Dimensions.paddingSize * 0.8),
            physics: const NeverScrollableScrollPhysics(),
            children: [
              CustomTitleHeadingWidget(
                text: Strings.recentTransactions,
                padding: EdgeInsets.only(top: Dimensions.marginSizeVertical),
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
                      status: data[index].status,
                      amount: data[index].requestAmount!,
                      payable: data[index].payable!,
                      title: data[index].transactionType!,
                      dateText: DateFormat.d().format(data[index].dateTime!),
                      transaction: data[index].trx!,
                      monthText: DateFormat.MMM().format(data[index].dateTime!),
                    );
                  },
                ),
              )
            ],
          ).customGlassWidget();
  }
}
