import 'package:carousel_slider/carousel_slider.dart';
import 'package:flip_card/flip_card.dart';
import 'package:flutter_html/flutter_html.dart';
import 'package:qrpay/backend/model/categories/virtual_card/virtual_card_sudo/virtual_card_sudo_info_model.dart';
import 'package:qrpay/extentions/custom_extentions.dart';
import 'package:qrpay/widgets/others/custom_glass/custom_glass_widget.dart';
import 'package:smooth_page_indicator/smooth_page_indicator.dart';

import '../../../../backend/utils/custom_loading_api.dart';
import '../../../../backend/utils/no_data_widget.dart';
import '../../../../controller/categories/virtual_card/sudo_virtual_card/virtual_card_sudo_controller.dart';
import '../../../../custom_assets/assets.gen.dart';
import '../../../../routes/routes.dart';
import '../../../../utils/basic_screen_imports.dart';
import '../../../../widgets/bottom_navbar/categorie_widget.dart';
import '../../../../widgets/bottom_navbar/transaction_history_widget.dart';
import 'sudo_add_fund_screen.dart';

class SudoVirtualCardScreen extends StatelessWidget {
  const SudoVirtualCardScreen({super.key, required this.controller});

  final VirtualSudoCardController controller;

  @override
  Widget build(BuildContext context) {
    return Obx(
      () => controller.isLoading
          ? const CustomLoadingAPI()
          : _bodyWidget(context),
    );
  }

  _bodyWidget(BuildContext context) {
    final data = controller.cardInfoModel.data.myCard;
    return Stack(
      children: [
        Column(
          children: [
            data.isEmpty ? _emptyCardWidget(context) : _cardSlider(context),
            _cardLimitShow(),
            data.isEmpty
                ? _createCardWidget(context)
                : _cardCategoriesWidget(context),
            const SizedBox()
          ],
        ),
        _draggableSheet(context),
      ],
    );
  }

  _draggableSheet(BuildContext context) {
    return DraggableScrollableSheet(
      builder: (_, scrollController) {
        return _recentTransWidget(context, scrollController);
      },
      initialChildSize: 0.45,
      minChildSize: 0.45,
      maxChildSize: 0.68,
    );
  }

  cardFontWidget(BuildContext context, List<MyCard> data, int index,
      CardBasicInfo cardData) {
    bool isTablet() {
      return MediaQuery.of(context).size.shortestSide >= 600;
    }

    return Container(
      alignment: Alignment.center,
      padding: EdgeInsets.only(
        bottom: Dimensions.marginSizeVertical * 0.5,
        left: Dimensions.paddingSize * 0.5,
        right: Dimensions.paddingSize * 2,
      ),
      height: MediaQuery.of(context).size.height * 0.30,
      width: MediaQuery.of(context).size.width * 0.9,
      decoration: BoxDecoration(
          image: DecorationImage(
            image: NetworkImage(controller.cardImage.value),
            fit: BoxFit.cover,
          ),
          borderRadius: BorderRadius.circular(Dimensions.radius * 1.5)),
      child: Stack(
        children: [
          Positioned(
            right: 0,
            top: 5,
            child: Image.network(
              controller.siteLogo.value,
              height: Dimensions.heightSize * 4,
              width: Dimensions.widthSize * 5,
            ),
          ),
          Column(
            mainAxisAlignment: MainAxisAlignment.center,
            crossAxisAlignment: crossStart,
            children: [
              verticalSpace(Dimensions.heightSize * 1.2),
              Padding(
                padding: EdgeInsets.only(left: Dimensions.paddingSize),
                child: TitleHeading3Widget(
                  text: controller.siteTitle.value,
                  color: CustomColor.whiteColor,
                  fontWeight: FontWeight.w800,
                ),
              ),
              verticalSpace(isTablet()
                  ? Dimensions.heightSize * 2
                  : Dimensions.heightSize * 5),
              Center(
                child: Text(
                  data[index].cardPan.formatCardNumber(),
                  style: CustomStyle.lightHeading4TextStyle.copyWith(
                    fontWeight: FontWeight.w700,
                    color: CustomColor.whiteColor.withOpacity(0.6),
                    fontSize: 22.sp,
                  ),
                ),
              ),
              verticalSpace(Dimensions.heightSize * 1.5),
              Padding(
                padding: EdgeInsets.only(left: Dimensions.paddingSize * 1.3),
                child: Row(
                  children: [
                    Column(
                      mainAxisAlignment: mainCenter,
                      crossAxisAlignment: crossStart,
                      children: [
                        CustomTitleHeadingWidget(
                          text:
                              '${data[index].expiryYear}/${data[index].expiryYear} ',
                          style: CustomStyle.f20w600pri
                              .copyWith(color: CustomColor.whiteColor),
                        ),
                        CustomTitleHeadingWidget(
                          text: Strings.expiryDate,
                          style: CustomStyle.f20w600pri.copyWith(
                              color: CustomColor.whiteColor.withOpacity(0.6),
                              fontWeight: FontWeight.w500,
                              fontSize: Dimensions.headingTextSize5),
                        ),
                      ],
                    ),
                  ],
                ),
              )
            ],
          ),
        ],
      ),
    );
  }

  cardBackWidget(BuildContext context, List<MyCard> data, int index,
      CardBasicInfo cardData) {
    return Container(
      alignment: Alignment.topRight,
      padding: EdgeInsets.only(
          left: Dimensions.paddingSize * 0.5,
          bottom: Dimensions.marginSizeVertical * 0.5,
          right: Dimensions.paddingSize * 0.5),
      height: MediaQuery.of(context).size.height * 0.30,
      width: MediaQuery.of(context).size.width * 0.9,
      decoration: BoxDecoration(
          image: DecorationImage(
            image: NetworkImage(controller.cardImage.value),
            fit: BoxFit.cover,
          ),
          borderRadius: BorderRadius.circular(Dimensions.radius * 1.5)),
      child: Column(
        crossAxisAlignment: crossEnd,
        mainAxisAlignment: mainEnd,
        children: [
          Column(
            mainAxisAlignment: MainAxisAlignment.center,
            crossAxisAlignment: crossStart,
            children: [
              verticalSpace(Dimensions.heightSize),
              horizontalSpace(Dimensions.widthSize),
              Column(
                mainAxisAlignment: mainCenter,
                children: [
                  CustomTitleHeadingWidget(
                    text: data[index].cvv,
                    style: CustomStyle.f20w600pri
                        .copyWith(color: CustomColor.whiteColor),
                  ),
                  CustomTitleHeadingWidget(
                    text: Strings.cvc,
                    style: CustomStyle.f20w600pri.copyWith(
                        color: CustomColor.whiteColor.withOpacity(0.6),
                        fontWeight: FontWeight.w500,
                        fontSize: Dimensions.headingTextSize5),
                  ),
                ],
              ),
            ],
          ),
          SizedBox(
            height: Dimensions.heightSize * 6,
            child: Html(
              data: cardData.cardBackDetails,
            ),
          )
        ],
      ),
    );
  }

  _emptyCardWidget(BuildContext context) {
    return FlipCard(
      front: emptyCardFontWidget(context),
      back: emptyCardBackWidget(context),
    );
  }

  emptyCardFontWidget(BuildContext context) {
    return Container(
      alignment: Alignment.center,
      padding: EdgeInsets.only(
        bottom: Dimensions.marginSizeVertical * 0.5,
        left: Dimensions.paddingSize * 0.5,
        right: Dimensions.paddingSize * 2,
      ),
      height: MediaQuery.of(context).size.height * 0.30,
      width: MediaQuery.of(context).size.width * 0.9,
      decoration: BoxDecoration(
          image: DecorationImage(
            image: NetworkImage(controller.cardImage.value),
            fit: BoxFit.cover,
          ),
          borderRadius: BorderRadius.circular(Dimensions.radius * 1.5)),
      child: Stack(
        children: [
          Positioned(
            right: 0,
            top: 12,
            child: Image.network(
              controller.siteLogo.value,
              height: Dimensions.heightSize * 4,
              width: Dimensions.widthSize * 5,
            ),
          ),
          Column(
            mainAxisAlignment: MainAxisAlignment.center,
            crossAxisAlignment: crossStart,
            children: [
              verticalSpace(Dimensions.heightSize * 1.5),
              Padding(
                padding: EdgeInsets.only(left: Dimensions.paddingSize * 1.2),
                child: TitleHeading3Widget(
                  text: controller.siteTitle.value,
                  color: CustomColor.whiteColor,
                  fontWeight: FontWeight.w800,
                ),
              ),
              verticalSpace(Dimensions.heightSize * 5),
              Center(
                child: Text(
                  "0000000000000000".formatCardNumber(),
                  style: TextStyle(
                    fontFamily: "AgencyFB",
                    fontSize: Dimensions.headingTextSize3,
                    fontWeight: FontWeight.w700,
                    color: CustomColor.whiteColor.withOpacity(0.6),
                  ),
                ),
              ),
              verticalSpace(Dimensions.heightSize * 1.5),
              Padding(
                padding: EdgeInsets.only(left: Dimensions.paddingSize * 1.1),
                child: Row(
                  children: [
                    Column(
                      mainAxisAlignment: mainCenter,
                      crossAxisAlignment: crossStart,
                      children: [
                        CustomTitleHeadingWidget(
                          text: '00/00',
                          style: CustomStyle.f20w600pri.copyWith(
                            color: CustomColor.whiteColor,
                            fontSize: Dimensions.headingTextSize3,
                          ),
                        ),
                        CustomTitleHeadingWidget(
                          text: Strings.expiryDate,
                          style: CustomStyle.f20w600pri.copyWith(
                            color: CustomColor.whiteColor.withOpacity(0.6),
                            fontWeight: FontWeight.w500,
                            fontSize: Dimensions.headingTextSize5,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              )
            ],
          ),
        ],
      ),
    );
  }

  emptyCardBackWidget(BuildContext context) {
    return Container(
      alignment: Alignment.topRight,
      padding: EdgeInsets.only(
          bottom: Dimensions.marginSizeVertical * 1.5,
          left: Dimensions.paddingSize * 0.5,
          right: Dimensions.paddingSize * 0.5),
      height: MediaQuery.of(context).size.height * 0.30,
      width: MediaQuery.of(context).size.width * 0.9,
      decoration: BoxDecoration(
          image: DecorationImage(
            image: NetworkImage(controller.cardImage.value),
            fit: BoxFit.cover,
          ),
          borderRadius: BorderRadius.circular(Dimensions.radius * 1.5)),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        crossAxisAlignment: crossStart,
        children: [
          verticalSpace(Dimensions.heightSize),
          horizontalSpace(Dimensions.widthSize),
          Column(
            mainAxisAlignment: mainCenter,
            children: [
              CustomTitleHeadingWidget(
                text: "000",
                style: CustomStyle.f20w600pri
                    .copyWith(color: CustomColor.whiteColor),
              ),
              CustomTitleHeadingWidget(
                text: Strings.cvc,
                style: CustomStyle.f20w600pri.copyWith(
                    color: CustomColor.whiteColor.withOpacity(0.6),
                    fontWeight: FontWeight.w500,
                    fontSize: Dimensions.headingTextSize5),
              ),
            ],
          ),
        ],
      ),
    );
  }

  _cardCategoriesWidget(BuildContext context) {
    final data = controller.cardInfoModel.data.myCard;
    return Container(
      margin: EdgeInsets.only(
          bottom: Dimensions.marginSizeVertical,
          top: Dimensions.marginSizeVertical * 0.2),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
        children: [
          CategoriesWidget(
              icon: Assets.icon.details,
              text: Strings.details,
              onTap: () {
                // controller.getCardDetailsData(data.cardId);
                controller.getCardDetailsData(
                  data[controller.activeIndicatorIndex.value].cardId,
                );
                Get.toNamed(Routes.sudoCardDetailsScreen);
              }),
          CategoriesWidget(
              icon: Assets.icon.myGift,
              text: Strings.fund,
              onTap: () {
                if (controller.cardInfoModel.data.myCard.isNotEmpty) {
                  Get.to(() => SudoAddFundScreen(
                        appBarTitle: Strings.addFund,
                      ));
                }
              }),
          Obx(
            () => controller.isMakeDefaultLoading
                ? const CustomLoadingAPI()
                : CategoriesWidget(
                    icon: Assets.icon.fund,
                    text: data[controller.activeIndicatorIndex.value].isDefault
                        ? Strings.removeDefault
                        : Strings.makeDefault,
                    onTap: () {
                      controller.makeCardDefaultProcess(
                          data[controller.activeIndicatorIndex.value].cardId);
                    }),
          ),
          CategoriesWidget(
              icon: Assets.icon.transaction,
              text: Strings.transaction,
              onTap: () {
                // controller.getCardTransactionData(
                //     controller.cardInfoModel.data.myCard.first.cardId);
              }),
        ],
      ),
    );
  }

  _recentTransWidget(BuildContext context, ScrollController scrollController) {
    final data = controller.cardInfoModel.data.transactions;
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
                          title: data[index].transactionType,
                          dateText: controller.getDay(data[index].dateTime),
                          transaction: data[index].trx,
                          monthText: controller.getMonth(data[index].dateTime),
                        );
                      }),
                )
              ],
            ).customGlassWidget()
          : Container(),
    );
  }

  _createCardWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.symmetric(
        horizontal: Dimensions.paddingSize * .5,
        vertical: Dimensions.paddingSize * .5,
      ),
      padding: EdgeInsets.symmetric(vertical: Dimensions.paddingSize * .5),
      child: PrimaryButton(
        title: Strings.createCard.tr,
        onPressed: () {
          Get.toNamed(Routes.sudoCreateCardScreen);
        },
        borderColor: CustomColor.primaryLightColor,
        buttonColor: CustomColor.primaryLightColor,
      ),
    );
  }

  _cardSlider(BuildContext context) {
    final data = controller.cardInfoModel.data.myCard;
    final cardData = controller.cardInfoModel.data.cardBasicInfo;
    return Container(
      height: MediaQuery.of(context).size.height * 0.34,
      padding: EdgeInsets.only(bottom: Dimensions.marginSizeVertical * 0.5),
      // color: CustomColor.screenBGColor,
      child: data.isNotEmpty
          ? Obx(
              () => Center(
                child: Column(
                  children: [
                    CarouselSlider.builder(
                      itemCount: data.length,
                      itemBuilder: (context, index, realIndex) {
                        return FlipCard(
                            front:
                                cardFontWidget(context, data, index, cardData),
                            back:
                                cardBackWidget(context, data, index, cardData));
                      },
                      options: CarouselOptions(
                        // enlargeCenterPage: true,
                        viewportFraction: 1,
                        height: MediaQuery.of(context).size.height * 0.29,
                        onPageChanged: (index, reason) {
                          return controller.changeIndicator(index);
                        },
                      ),
                    ),
                    SizedBox(
                      height: Dimensions.heightSize,
                    ),
                    _buildIndicator(context),
                  ],
                ),
              ),
            )
          : const NoDataWidget(),
    );
  }

  _buildIndicator(BuildContext context) {
    final data = controller.cardInfoModel.data.myCard;
    return AnimatedSmoothIndicator(
      activeIndex: controller.activeIndicatorIndex.value,
      count: data.length,
      effect: SlideEffect(
        dotHeight: 8,
        dotWidth: 8,
        activeDotColor: CustomColor.primaryLightColor,
        dotColor: Colors.grey.withOpacity(0.5),
      ),
    );
  }

  _cardLimitShow() {
    return Padding(
      padding: EdgeInsets.only(top: Dimensions.paddingVerticalSize * 0.3),
      child: Row(
        mainAxisAlignment: mainCenter,
        children: const [
          // const TitleHeading3Widget(text: Strings.myCard),
          // horizontalSpace(Dimensions.widthSize * 0.4),
          // TitleHeading3Widget(
          //     text:
          //         '${controller.cardInfoModel.data.myCard.length}/${controller.cardInfoModel.data.cardBasicInfo.}'),
        ],
      ),
    );
  }
}
